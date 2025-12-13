<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotIn;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Variables\Variable;
use RuntimeException;

use function array_map;

/**
 * Compiles natural language AST nodes into Proposition objects.
 *
 * Translates the abstract syntax tree produced by the NaturalLanguageParser
 * into the internal rule engine's Proposition structure. Handles logical
 * operators, comparison operators, range checks, list membership, and string
 * operations expressed in human-readable syntax.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class NaturalLanguageCompiler
{
    /**
     * Field resolver for mapping natural language field names to variables.
     */
    private FieldResolver $fieldResolver;

    /**
     * Create a new natural language compiler.
     *
     * @param RuleBuilder $ruleBuilder rule builder instance used to resolve field references
     *                                 and manage variable context during AST compilation
     */
    public function __construct(RuleBuilder $ruleBuilder)
    {
        $this->fieldResolver = new FieldResolver($ruleBuilder);
    }

    /**
     * Compile an AST node into a Proposition.
     *
     * Recursively processes AST nodes, dispatching to specialized compilation
     * methods based on the node type (logical, comparison, between, in, string).
     *
     * @param array<string, mixed> $ast AST node with type and associated data
     *
     * @throws RuntimeException If AST node type is unknown or unsupported
     *
     * @return Proposition Compiled proposition representing the AST node's logic
     */
    public function compile(array $ast): Proposition
    {
        /** @var string $type */
        $type = $ast['type'] ?? throw new RuntimeException('Missing AST node type');

        return match ($type) {
            'logical' => $this->compileLogical($ast),
            'comparison' => $this->compileComparison($ast),
            'between' => $this->compileBetween($ast),
            'in' => $this->compileIn($ast),
            'string' => $this->compileString($ast),
            default => throw new RuntimeException('Unknown AST node type: '.$type),
        };
    }

    /**
     * Compile a logical operator AST node.
     *
     * @param array<string, mixed> $ast AST node with 'operator' and 'conditions' keys
     *
     * @throws RuntimeException If logical operator is unknown
     *
     * @return Proposition LogicalAnd or LogicalOr proposition combining conditions
     */
    private function compileLogical(array $ast): Proposition
    {
        /** @var array<array<string, mixed>> $conditionsData */
        $conditionsData = $ast['conditions'] ?? throw new RuntimeException('Missing conditions');

        $conditions = array_map(
            $this->compile(...),
            $conditionsData,
        );

        /** @var string $operator */
        $operator = $ast['operator'] ?? throw new RuntimeException('Missing operator');

        return match ($operator) {
            'and' => new LogicalAnd($conditions),
            'or' => new LogicalOr($conditions),
            default => throw new RuntimeException('Unknown logical operator: '.$operator),
        };
    }

    /**
     * Compile a comparison operator AST node.
     *
     * @param array<string, mixed> $ast AST node with 'operator', 'field', and 'value' keys
     *
     * @throws RuntimeException If comparison operator is unknown
     *
     * @return Proposition Comparison proposition (EqualTo, GreaterThan, etc.)
     */
    private function compileComparison(array $ast): Proposition
    {
        /** @var string $field */
        $field = $ast['field'] ?? throw new RuntimeException('Missing field');
        $variable = $this->fieldResolver->resolve($field);
        $value = new Variable(null, $ast['value'] ?? null);

        /** @var string $operator */
        $operator = $ast['operator'] ?? throw new RuntimeException('Missing operator');

        return match ($operator) {
            'eq' => new EqualTo($variable, $value),
            'ne' => new NotEqualTo($variable, $value),
            'gt' => new GreaterThan($variable, $value),
            'gte' => new GreaterThanOrEqualTo($variable, $value),
            'lt' => new LessThan($variable, $value),
            'lte' => new LessThanOrEqualTo($variable, $value),
            default => throw new RuntimeException('Unknown comparison operator: '.$operator),
        };
    }

    /**
     * Compile a between range check AST node.
     *
     * @param  array<string, mixed> $ast AST node with 'field', 'min', and 'max' keys
     * @return Proposition          Between proposition checking if field is within range
     */
    private function compileBetween(array $ast): Proposition
    {
        /** @var string $field */
        $field = $ast['field'] ?? throw new RuntimeException('Missing field');
        $variable = $this->fieldResolver->resolve($field);
        $min = new Variable(null, $ast['min'] ?? null);
        $max = new Variable(null, $ast['max'] ?? null);

        return new Between($variable, $min, $max);
    }

    /**
     * Compile a list membership check AST node.
     *
     * @param  array<string, mixed> $ast AST node with 'field', 'values', and 'negated' keys
     * @return Proposition          In or NotIn proposition checking list membership
     */
    private function compileIn(array $ast): Proposition
    {
        /** @var string $field */
        $field = $ast['field'] ?? throw new RuntimeException('Missing field');
        $variable = $this->fieldResolver->resolve($field);
        $values = new Variable(null, $ast['values'] ?? []);

        if ($ast['negated'] ?? false) {
            return new NotIn($variable, $values);
        }

        return new In($variable, $values);
    }

    /**
     * Compile a string operation AST node.
     *
     * @param array<string, mixed> $ast AST node with 'operation', 'field', and 'value' keys
     *
     * @throws RuntimeException If string operation is unknown
     *
     * @return Proposition String operation proposition (Contains, StartsWith, EndsWith)
     */
    private function compileString(array $ast): Proposition
    {
        /** @var string $field */
        $field = $ast['field'] ?? throw new RuntimeException('Missing field');
        $variable = $this->fieldResolver->resolve($field);
        $value = new Variable(null, $ast['value'] ?? '');

        /** @var string $operation */
        $operation = $ast['operation'] ?? throw new RuntimeException('Missing operation');

        return match ($operation) {
            'contains' => new StringContains($variable, $value),
            'startsWith' => new StartsWith($variable, $value),
            'endsWith' => new EndsWith($variable, $value),
            default => throw new RuntimeException('Unknown string operation: '.$operation),
        };
    }
}
