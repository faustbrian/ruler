<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

use InvalidArgumentException;

use const JSON_THROW_ON_ERROR;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_map;
use function count;
use function gettype;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function throw_unless;

/**
 * Parses GraphQL filter syntax into abstract syntax tree nodes.
 *
 * GraphQLParser transforms GraphQL-style filter queries (in JSON string or PHP array
 * format) into a structured abstract syntax tree of GraphQLNode instances. The parser
 * supports logical operators (AND/OR/NOT), comparison operators, list operations, string
 * operations, null checks, type validations, and nested object notation for accessing
 * deeply nested fields.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class GraphQLParser
{
    /**
     * Recognized GraphQL filter operators.
     *
     * @var array<int, string>
     */
    private const array OPERATORS = [
        'eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'in', 'notIn',
        'contains', 'notContains', 'containsInsensitive', 'notContainsInsensitive',
        'startsWith', 'endsWith', 'match', 'isNull', 'isType',
    ];

    /**
     * Parse GraphQL filter query into an abstract syntax tree.
     *
     * Accepts filter queries in JSON string or PHP array format and transforms
     * them into GraphQLNode instances representing the query structure. JSON
     * strings are decoded with strict error handling before parsing.
     *
     * @param  array<string, mixed>|string $filter graphQL filter query as JSON string or PHP array
     *                                             supporting logical, comparison, list, string,
     *                                             null check, and type validation operations
     * @return GraphQLNode                 root node of the abstract syntax tree representing the filter logic
     */
    public function parse(string|array $filter): GraphQLNode
    {
        if (is_string($filter)) {
            $decoded = json_decode($filter, true, 512, JSON_THROW_ON_ERROR);

            throw_unless(is_array($decoded), InvalidArgumentException::class, 'Invalid filter: expected array, got '.gettype($decoded));

            /** @var array<string, mixed> $decoded */
            $filter = $decoded;
        }

        return $this->parseFilter($filter);
    }

    /**
     * Parse a specific operator and its operand into an AST node.
     *
     * Creates the appropriate node type based on the operator: ComparisonNode
     * for eq/ne/gt/gte/lt/lte, ListNode for in/notIn, StringNode for string
     * operations, NullNode for isNull, and TypeNode for isType. The
     * containsInsensitive operator is normalized to contains with case
     * insensitivity flag.
     *
     * @param string $field    field name or path being tested
     * @param string $operator operator name defining the type of comparison or test
     * @param mixed  $value    operand value for the operator
     *
     * @throws InvalidArgumentException when the operator is not recognized
     *
     * @return GraphQLNode parsed AST node for the specific operator
     */
    private static function parseOperator(string $field, string $operator, mixed $value): GraphQLNode
    {
        return match ($operator) {
            'eq', 'ne', 'gt', 'gte', 'lt', 'lte' => new ComparisonNode($operator, $field, $value),
            'in', 'notIn' => new ListNode($operator, $field, is_array($value) ? $value : throw new InvalidArgumentException($operator.' operator expects an array')),
            'contains', 'notContains' => new StringNode($operator, $field, is_string($value) ? $value : throw new InvalidArgumentException($operator.' operator expects a string'), false),
            'containsInsensitive' => new StringNode('contains', $field, is_string($value) ? $value : throw new InvalidArgumentException('containsInsensitive operator expects a string'), true),
            'notContainsInsensitive' => new StringNode('notContains', $field, is_string($value) ? $value : throw new InvalidArgumentException('notContainsInsensitive operator expects a string'), true),
            'startsWith', 'endsWith', 'match' => new StringNode($operator, $field, is_string($value) ? $value : throw new InvalidArgumentException($operator.' operator expects a string'), false),
            'isNull' => new NullNode($field, is_bool($value) ? $value : throw new InvalidArgumentException('isNull operator expects a boolean')),
            'isType' => new TypeNode($field, is_string($value) ? $value : throw new InvalidArgumentException('isType operator expects a string')),
            default => throw new InvalidArgumentException('Unsupported operator: '.$operator),
        };
    }

    /**
     * Check if an array contains recognized operator keys.
     *
     * Determines whether an array represents operator constraints or a nested
     * object structure by checking for the presence of recognized operator names
     * in the array keys. This distinction guides parsing logic between explicit
     * operators and nested field access.
     *
     * @param  array<string, mixed> $value array to check for operator keys
     * @return bool                 true when the array contains at least one recognized operator key
     */
    private static function hasOperators(array $value): bool
    {
        return array_intersect(array_keys($value), self::OPERATORS) !== [];
    }

    /**
     * Parse a filter structure into AST nodes.
     *
     * Processes logical operators (AND/OR/NOT) at the top level, then handles
     * field-level conditions. Multiple field conditions at the same level are
     * implicitly combined with AND logic. Uppercase logical operator keys
     * (AND/OR/NOT) are recognized as explicit logical operations.
     *
     * @param  array<string, mixed> $filter filter structure containing logical operators or field conditions
     * @return GraphQLNode          parsed AST node representing the filter logic
     */
    private function parseFilter(array $filter): GraphQLNode
    {
        // Handle logical operators (uppercase)
        if (array_key_exists('AND', $filter)) {
            $andValue = $filter['AND'];

            throw_unless(is_array($andValue), InvalidArgumentException::class, 'AND operator expects an array');

            /** @var array<int, GraphQLNode> $conditions */
            $conditions = array_map(
                function (mixed $f): GraphQLNode {
                    throw_unless(is_array($f), InvalidArgumentException::class, 'Invalid filter in AND');

                    /** @var array<string, mixed> $f */

                    return $this->parseFilter($f);
                },
                $andValue,
            );

            return new LogicalNode('and', $conditions);
        }

        if (array_key_exists('OR', $filter)) {
            $orValue = $filter['OR'];

            throw_unless(is_array($orValue), InvalidArgumentException::class, 'OR operator expects an array');

            /** @var array<int, GraphQLNode> $conditions */
            $conditions = array_map(
                function (mixed $f): GraphQLNode {
                    throw_unless(is_array($f), InvalidArgumentException::class, 'Invalid filter in OR');

                    /** @var array<string, mixed> $f */

                    return $this->parseFilter($f);
                },
                $orValue,
            );

            return new LogicalNode('or', $conditions);
        }

        if (array_key_exists('NOT', $filter)) {
            $notValue = $filter['NOT'];

            throw_unless(is_array($notValue), InvalidArgumentException::class, 'NOT operator expects an array');

            /** @var array<string, mixed> $notValue */

            return new LogicalNode('not', [
                $this->parseFilter($notValue),
            ]);
        }

        // Handle field conditions (implicit AND)
        $conditions = [];

        foreach ($filter as $field => $value) {
            $conditions[] = $this->parseFieldCondition($field, $value);
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return new LogicalNode('and', $conditions);
    }

    /**
     * Parse a single field condition into an AST node.
     *
     * Handles three cases: implicit equality for scalar values, nested object
     * notation for accessing deep fields (converted to dot notation), and
     * explicit operators on field values. Multiple operators on the same field
     * are combined with implicit AND logic.
     *
     * @param  string      $field field name or path being tested
     * @param  mixed       $value field value, operator structure, or nested object for deep field access
     * @return GraphQLNode parsed AST node representing the field condition
     */
    private function parseFieldCondition(string $field, mixed $value): GraphQLNode
    {
        // Implicit equality
        if (!is_array($value)) {
            return new ComparisonNode('eq', $field, $value);
        }

        // Nested object (dot notation alternative)
        /** @var array<string, mixed> $value */
        if (!self::hasOperators($value)) {
            // Flatten: {user: {age: {gte: 18}}} => user.age >= 18
            return $this->parseNestedObject($field, $value);
        }

        // Handle operators
        $conditions = [];

        foreach ($value as $operator => $operandValue) {
            $conditions[] = self::parseOperator($field, $operator, $operandValue);
        }

        return count($conditions) === 1
            ? $conditions[0]
            : new LogicalNode('and', $conditions);
    }

    /**
     * Parse nested object notation into flattened dot notation paths.
     *
     * Converts nested object structures like {user: {age: {gte: 18}}} into
     * flat field paths like "user.age" for simpler field resolution during
     * evaluation. Recursively processes nested objects to handle arbitrary
     * depth. Multiple conditions in a nested object are combined with AND logic.
     *
     * @param  string               $prefix accumulated field path prefix from parent levels
     * @param  array<string, mixed> $obj    nested object structure to flatten
     * @return GraphQLNode          parsed AST node representing the flattened field conditions
     */
    private function parseNestedObject(string $prefix, array $obj): GraphQLNode
    {
        $conditions = [];

        foreach ($obj as $key => $value) {
            $path = $prefix.'.'.$key;

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                if (!self::hasOperators($value)) {
                    $conditions[] = $this->parseNestedObject($path, $value);
                } else {
                    $conditions[] = $this->parseFieldCondition($path, $value);
                }
            } else {
                $conditions[] = $this->parseFieldCondition($path, $value);
            }
        }

        return count($conditions) === 1
            ? $conditions[0]
            : new LogicalNode('and', $conditions);
    }
}
