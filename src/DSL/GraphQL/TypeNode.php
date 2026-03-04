<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Represents a type validation operation in GraphQL filter queries.
 *
 * TypeNode encapsulates operations that validate a field's value type against
 * expected type constraints. These nodes enable runtime type checking within
 * filter expressions, supporting validation for null, string, numeric, boolean,
 * and array types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class TypeNode extends GraphQLNode
{
    /**
     * Create a new type validation node.
     *
     * @param string $field        The field name to access from the evaluation context. Supports
     *                             dot notation for nested field access (e.g., "user.metadata"). The
     *                             field's value type is validated against the expected type during
     *                             rule evaluation.
     * @param string $expectedType The type that the field value must match. Supported values: "null"
     *                             (value is null), "string" (value is string), "number" or "numeric"
     *                             (value is numeric), "boolean" or "bool" (value is boolean), "array"
     *                             (value is array). Multiple aliases exist for developer convenience.
     *                             The type determines which type operator class is instantiated.
     */
    public function __construct(
        public readonly string $field,
        public readonly string $expectedType,
    ) {}
}
