<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Represents a list membership test operation in GraphQL filter queries.
 *
 * ListNode encapsulates operations that test whether a field's value is present
 * in or absent from a specified list of values. These nodes are used for "in"
 * and "notIn" operations in GraphQL filter syntax, supporting efficient membership
 * testing during rule evaluation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ListNode extends GraphQLNode
{
    /**
     * Create a new list membership test node.
     *
     * @param string                  $operator The list operation to perform. Supported values: "in" tests
     *                                          for value presence in the list, "notIn" tests for value absence
     *                                          from the list. This determines which list operator class is
     *                                          instantiated during compilation.
     * @param string                  $field    The field name to access from the evaluation context. Supports
     *                                          dot notation for nested field access (e.g., "user.role"). The
     *                                          field's value is tested for membership in the provided list.
     * @param array<array-key, mixed> $values   The list of values to test against. Can contain any type of
     *                                          values (strings, integers, booleans, etc.). The field value is
     *                                          compared against each element for membership determination.
     */
    public function __construct(
        public readonly string $operator,
        public readonly string $field,
        public readonly array $values,
    ) {}
}
