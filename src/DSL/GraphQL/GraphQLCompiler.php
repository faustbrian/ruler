<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

use Cline\Ruler\Core\Proposition;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotIn;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringContainsInsensitive;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Operators\String\StringDoesNotContainInsensitive;
use Cline\Ruler\Operators\Type\IsArray;
use Cline\Ruler\Operators\Type\IsBoolean;
use Cline\Ruler\Operators\Type\IsNull;
use Cline\Ruler\Operators\Type\IsNumeric;
use Cline\Ruler\Operators\Type\IsString;
use Cline\Ruler\Variables\Variable;
use InvalidArgumentException;
use RuntimeException;

use function array_map;

/**
 * Compiles GraphQL filter AST nodes into executable rule propositions.
 *
 * GraphQLCompiler traverses the abstract syntax tree produced by GraphQLParser
 * and transforms each node into corresponding Proposition instances that can be
 * evaluated against data contexts. The compiler handles logical combinators,
 * comparison operations, list operations, string operations, null checks, and
 * type validations.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class GraphQLCompiler
{
    /**
     * Create a new GraphQL compiler instance.
     *
     * @param FieldResolver $fieldResolver resolves field paths from GraphQL queries into
     *                                     Variable instances that can access nested data
     *                                     structures using dot notation during evaluation
     */
    public function __construct(
        private FieldResolver $fieldResolver,
    ) {}

    /**
     * Compile a GraphQL AST into an executable proposition.
     *
     * @param  GraphQLNode $ast the root node of the GraphQL query abstract syntax tree
     *                          produced by the parser, representing the complete filter logic
     * @return Proposition the compiled proposition ready for evaluation against data contexts
     */
    public function compile(GraphQLNode $ast): Proposition
    {
        return $this->compileNode($ast);
    }

    /**
     * Compile a single AST node into its corresponding proposition.
     *
     * Dispatches to specialized compilation methods based on node type. Supports
     * logical operations (AND/OR/NOT), comparisons (eq/ne/gt/gte/lt/lte), list
     * operations (in/notIn), string operations (contains/startsWith/endsWith/match),
     * null checks, and type validations.
     *
     * @param GraphQLNode $node the AST node to compile into a proposition
     *
     * @throws RuntimeException when encountering an unknown or unsupported node type
     *
     * @return Proposition the compiled proposition for this node
     */
    private function compileNode(GraphQLNode $node): Proposition
    {
        return match (true) {
            $node instanceof LogicalNode => $this->compileLogical($node),
            $node instanceof ComparisonNode => $this->compileComparison($node),
            $node instanceof ListNode => $this->compileList($node),
            $node instanceof StringNode => $this->compileString($node),
            $node instanceof NullNode => $this->compileNull($node),
            $node instanceof TypeNode => $this->compileType($node),
            default => throw new RuntimeException('Unknown node type: '.$node::class),
        };
    }

    /**
     * Compile a logical operation node (AND/OR/NOT).
     *
     * Recursively compiles all child conditions and combines them using the
     * appropriate logical operator. For NOT operations, the conditions array
     * should contain exactly one element.
     *
     * @param LogicalNode $node the logical operation node containing operator and conditions
     *
     * @throws InvalidArgumentException when the operator is not one of: and, or, not
     *
     * @return Proposition the compiled logical proposition combining all child conditions
     */
    private function compileLogical(LogicalNode $node): Proposition
    {
        $compiledConditions = array_map(
            fn (GraphQLNode $c): Proposition => $this->compileNode($c),
            $node->conditions,
        );

        return match ($node->operator) {
            'and' => new LogicalAnd($compiledConditions),
            'or' => new LogicalOr($compiledConditions),
            'not' => new LogicalNot($compiledConditions),
            default => throw new InvalidArgumentException('Unsupported logical operator: '.$node->operator),
        };
    }

    /**
     * Compile a comparison operation node (eq/ne/gt/gte/lt/lte).
     *
     * Resolves the field reference using dot notation and creates the appropriate
     * comparison operator instance with the field and comparison value. The value
     * is wrapped in a Variable for consistent handling during evaluation.
     *
     * @param ComparisonNode $node the comparison operation node containing field, operator, and value
     *
     * @throws InvalidArgumentException when the operator is not a valid comparison operator
     *
     * @return Proposition the compiled comparison proposition
     */
    private function compileComparison(ComparisonNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $value = new Variable(null, $node->value);

        return match ($node->operator) {
            'eq' => new EqualTo($field, $value),
            'ne' => new NotEqualTo($field, $value),
            'gt' => new GreaterThan($field, $value),
            'gte' => new GreaterThanOrEqualTo($field, $value),
            'lt' => new LessThan($field, $value),
            'lte' => new LessThanOrEqualTo($field, $value),
            default => throw new InvalidArgumentException('Unsupported comparison operator: '.$node->operator),
        };
    }

    /**
     * Compile a list operation node (in/notIn).
     *
     * Creates membership test propositions that check whether a field's value
     * is present in (or absent from) a specified list of values. The values
     * array is wrapped in a Variable for evaluation.
     *
     * @param ListNode $node the list operation node containing field, operator, and values array
     *
     * @throws InvalidArgumentException when the operator is not one of: in, notIn
     *
     * @return Proposition the compiled list membership proposition
     */
    private function compileList(ListNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $value = new Variable(null, $node->values);

        return match ($node->operator) {
            'in' => new In($field, $value),
            'notIn' => new NotIn($field, $value),
            default => throw new InvalidArgumentException('Unsupported list operator: '.$node->operator),
        };
    }

    /**
     * Compile a string operation node (contains/notContains/startsWith/endsWith/match).
     *
     * Creates string matching propositions with optional case sensitivity control.
     * The 'contains' and 'notContains' operators respect the caseInsensitive flag,
     * while other operators are case-sensitive. The 'match' operator wraps values
     * in regex delimiters for pattern matching.
     *
     * @param StringNode $node the string operation node with field, operator, value, and case sensitivity
     *
     * @throws InvalidArgumentException when the operator is not a valid string operator
     *
     * @return Proposition the compiled string operation proposition
     */
    private function compileString(StringNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $value = new Variable(null, $node->value);

        return match ($node->operator) {
            'contains' => $node->caseInsensitive
                ? new StringContainsInsensitive($field, $value)
                : new StringContains($field, $value),
            'notContains' => $node->caseInsensitive
                ? new StringDoesNotContainInsensitive($field, $value)
                : new StringDoesNotContain($field, $value),
            'startsWith' => new StartsWith($field, $value),
            'endsWith' => new EndsWith($field, $value),
            'match' => new Matches($field, new Variable(null, '/'.$node->value.'/')),
            default => throw new InvalidArgumentException('Unsupported string operator: '.$node->operator),
        };
    }

    /**
     * Compile a null check operation node.
     *
     * Creates a null validation proposition, optionally negated based on the
     * shouldBeNull flag. When shouldBeNull is false, the IsNull check is
     * wrapped in a LogicalNot to test for non-null values.
     *
     * @param  NullNode    $node the null check node specifying field and expected null state
     * @return Proposition the compiled null check proposition, potentially negated
     */
    private function compileNull(NullNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);
        $isNullCheck = new IsNull($field);

        return $node->shouldBeNull
            ? $isNullCheck
            : new LogicalNot([$isNullCheck]);
    }

    /**
     * Compile a type validation node.
     *
     * Creates type checking propositions that validate field values against
     * expected types. Supports null, string, number/numeric, boolean/bool,
     * and array types. The number and boolean types accept multiple aliases
     * for developer convenience.
     *
     * @param TypeNode $node the type validation node specifying field and expected type
     *
     * @throws InvalidArgumentException when the type is not a recognized type string
     *
     * @return Proposition the compiled type validation proposition
     */
    private function compileType(TypeNode $node): Proposition
    {
        $field = $this->fieldResolver->resolve($node->field);

        return match ($node->expectedType) {
            'null' => new IsNull($field),
            'string' => new IsString($field),
            'number', 'numeric' => new IsNumeric($field),
            'boolean', 'bool' => new IsBoolean($field),
            'array' => new IsArray($field),
            default => throw new InvalidArgumentException('Unsupported type: '.$node->expectedType),
        };
    }
}
