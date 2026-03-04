<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Represents a GraphQL comparison operation node in the query AST.
 *
 * ComparisonNode encapsulates a single comparison operation in a GraphQL query,
 * defining the field to compare, the comparison operator to apply, and the value
 * to compare against. These nodes are constructed during GraphQL query parsing and
 * used to build propositional logic for rule evaluation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ComparisonNode extends GraphQLNode
{
    /**
     * Create a new GraphQL comparison node.
     *
     * @param string $operator The comparison operator defining the type of comparison
     *                         to perform. Supported values: "eq" (equal), "ne" (not equal),
     *                         "gt" (greater than), "gte" (greater than or equal), "lt" (less than),
     *                         "lte" (less than or equal). The operator determines which comparison
     *                         operator class will be instantiated during rule evaluation.
     * @param string $field    The field name to access from the evaluation context. Supports
     *                         dot notation for nested field access (e.g., "user.email"). This
     *                         field is resolved against the context values during evaluation.
     * @param mixed  $value    The comparison value to test the field against. Can be any type
     *                         (string, int, bool, array, etc.) depending on the field type and
     *                         comparison operator. This value is used as the right-hand operand
     *                         in the comparison operation.
     */
    public function __construct(
        public readonly string $operator,
        public readonly string $field,
        public readonly mixed $value,
    ) {}
}
