<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Represents a null check operation in GraphQL filter queries.
 *
 * NullNode encapsulates operations that test whether a field's value is null
 * or not null. These nodes support both positive null checks (field must be null)
 * and negative null checks (field must not be null) through the shouldBeNull flag,
 * enabling flexible null validation in filter expressions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NullNode extends GraphQLNode
{
    /**
     * Create a new null check operation node.
     *
     * @param string $field        The field name to access from the evaluation context. Supports
     *                             dot notation for nested field access (e.g., "user.email"). The
     *                             field's value is tested for null or non-null state depending on
     *                             the shouldBeNull flag.
     * @param bool   $shouldBeNull Whether the field value should be null (true) or should not be
     *                             null (false). When true, creates an IsNull check. When false,
     *                             creates a negated IsNull check to test for non-null values. This
     *                             enables both positive and negative null validations in queries.
     */
    public function __construct(
        public readonly string $field,
        public readonly bool $shouldBeNull,
    ) {}
}
