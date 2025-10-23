<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Variables\Variable as BaseVariable;
use LogicException;

use function array_map;
use function explode;
use function implode;
use function is_numeric;
use function preg_quote;
use function sprintf;
use function str_contains;
use function throw_unless;

/**
 * Compiles LDAP filter AST to Ruler Operator trees.
 *
 * Converts LDAP filter nodes into Ruler Propositions, handling type
 * coercion, wildcard patterns, and field resolution.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LDAPCompiler
{
    /**
     * Create a new LDAP compiler instance.
     *
     * @param FieldResolver        $fieldResolver    Resolves dot-notation field paths to Variable instances.
     *                                               Handles nested object access and converts LDAP attribute
     *                                               names to Ruler field references for evaluation.
     * @param LDAPOperatorRegistry $operatorRegistry Maps LDAP operators to Ruler operator class names.
     *                                               Provides lookup for both comparison operators (=, >=, etc.)
     *                                               and logical operators (and, or, not) to their implementations.
     */
    public function __construct(
        private FieldResolver $fieldResolver,
        private LDAPOperatorRegistry $operatorRegistry,
    ) {}

    /**
     * Compiles LDAP AST into a Proposition.
     *
     * Transforms the parsed LDAP filter Abstract Syntax Tree into a Proposition
     * tree that can be evaluated against Context data.
     *
     * @param LDAPNode $ast Root node of the LDAP filter AST from the parser
     *
     * @throws LogicException If the AST cannot be compiled to a Proposition
     *
     * @return Proposition The compiled Proposition tree ready for evaluation
     */
    public function compile(LDAPNode $ast): Proposition
    {
        $result = $this->compileNode($ast);

        throw_unless($result instanceof Proposition, LogicException::class, 'LDAP filter must compile to a Proposition');

        return $result;
    }

    /**
     * Parses value string to appropriate PHP type.
     *
     * Performs type coercion based on value format:
     * - "true"/"false" become booleans
     * - "null" becomes null
     * - Numeric strings become int or float
     * - Everything else remains a string
     *
     * @param  string $value The string value to parse and coerce
     * @return mixed  The coerced value in appropriate PHP type
     */
    private static function parseValue(string $value): mixed
    {
        // Boolean
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        // Null
        if ($value === 'null') {
            return null;
        }

        // Number (int or float)
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // String (default)
        return $value;
    }

    /**
     * Compiles a single AST node recursively.
     *
     * Dispatches to specialized compilation methods based on node type.
     *
     * @param LDAPNode $node The AST node to compile
     *
     * @throws LogicException If an unknown node type is encountered
     *
     * @return BaseVariable|Proposition|Variable The compiled component
     */
    private function compileNode(LDAPNode $node): Proposition|Variable|BaseVariable
    {
        return match (true) {
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof WildcardNode => $this->compileWildcard($node),
            $node instanceof PresenceNode => $this->compilePresence($node),
            $node instanceof ApproximateNode => $this->compileApproximate($node),
            default => throw new LogicException(sprintf('Unknown node type: %s', $node::class)),
        };
    }

    /**
     * Compiles a logical operator node (AND, OR, NOT).
     *
     * Recursively compiles all child conditions and wraps them in the
     * appropriate logical operator instance.
     *
     * @param LogicalNode $node The logical operator node to compile
     *
     * @throws LogicException If the operator class doesn't implement Proposition
     *
     * @return Proposition The compiled logical operator
     */
    private function compileLogical(LogicalNode $node): Proposition
    {
        $operatorClass = $this->operatorRegistry->getLogical($node->operator);

        $compiledConditions = array_map(
            $this->compileNode(...),
            $node->conditions,
        );

        $operator = new $operatorClass($compiledConditions);

        throw_unless($operator instanceof Proposition, LogicException::class, sprintf('Logical operator %s must implement Proposition', $operatorClass));

        return $operator;
    }

    /**
     * Compiles a comparison node (=, >=, <=, >, <, !=).
     *
     * Resolves the field reference, coerces the value to the appropriate type,
     * and creates the corresponding comparison operator instance.
     *
     * @param ComparisonNode $node The comparison node to compile
     *
     * @throws LogicException If the operator class doesn't implement Proposition
     *
     * @return Proposition The compiled comparison operator
     */
    private function compileComparison(ComparisonNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);
        $value = self::parseValue($node->value);

        // Wrap raw value in BaseVariable
        $valueOperand = new BaseVariable(null, $value);

        $operatorClass = $this->operatorRegistry->getComparison($node->operator);

        $operator = new $operatorClass($field, $valueOperand);

        throw_unless($operator instanceof Proposition, LogicException::class, sprintf('Comparison operator %s must implement Proposition', $operatorClass));

        return $operator;
    }

    /**
     * Compiles a wildcard pattern node (*, prefix*, *suffix, *contains*).
     *
     * Converts LDAP wildcard syntax to regex patterns. Splits on asterisks,
     * escapes each literal part, and joins with .* for regex matching.
     *
     * @param WildcardNode $node The wildcard pattern node to compile
     *
     * @throws LogicException If the Matches operator doesn't implement Proposition
     *
     * @return Proposition The compiled wildcard match using Matches operator
     */
    private function compileWildcard(WildcardNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);

        // Convert LDAP wildcard to regex pattern
        // Split on * and escape each part, then join with .*
        $parts = explode('*', $node->pattern);
        $parts = array_map(fn (string $part): string => preg_quote($part, '/'), $parts);

        $pattern = '/^'.implode('.*', $parts).'$/';

        // Wrap pattern in BaseVariable
        $patternOperand = new BaseVariable(null, $pattern);

        return new Matches($field, $patternOperand);
    }

    /**
     * Compiles a presence check node (field=*).
     *
     * Translates LDAP presence checks to "field is not null" logic by negating
     * an equality comparison with null.
     *
     * @param PresenceNode $node The presence check node to compile
     *
     * @throws LogicException If the NOT operator doesn't implement Proposition
     *
     * @return Proposition The compiled presence check as NOT(field == null)
     */
    private function compilePresence(PresenceNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);

        // Field exists = NOT (field == null)
        $equalToClass = $this->operatorRegistry->getComparison('=');
        $notClass = $this->operatorRegistry->getLogical('not');

        $nullValue = new BaseVariable(null, null);
        $isNull = new $equalToClass($field, $nullValue);
        $operator = new $notClass([$isNull]);

        throw_unless($operator instanceof Proposition, LogicException::class, 'NOT operator must implement Proposition');

        return $operator;
    }

    /**
     * Compiles an approximate match node (~=).
     *
     * Implements fuzzy matching as case-insensitive substring search using regex.
     *
     * @param ApproximateNode $node The approximate match node to compile
     *
     * @throws LogicException If the Matches operator doesn't implement Proposition
     *
     * @return Proposition The compiled approximate match using case-insensitive regex
     */
    private function compileApproximate(ApproximateNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->attribute);

        // Approximate match - use case-insensitive regex containing the value
        $pattern = '/'.preg_quote($node->value, '/').'/i';

        // Wrap pattern in BaseVariable
        $patternOperand = new BaseVariable(null, $pattern);

        return new Matches($field, $patternOperand);
    }
}
