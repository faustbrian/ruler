<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

/**
 * Represents a field reference in SQL WHERE clause.
 *
 * Encapsulates references to database columns or nested fields using
 * dot-notation paths. The field name is resolved during compilation
 * to map to the appropriate data source location.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class FieldNode extends SqlNode
{
    /**
     * Create a new field reference node.
     *
     * @param string $fieldName Name of the database field or column being referenced.
     *                          Supports dot-notation for nested field access (e.g., "user.email").
     *                          The field name is case-sensitive and resolved during compilation
     *                          via the configured FieldResolver.
     */
    public function __construct(
        public string $fieldName,
    ) {}
}
