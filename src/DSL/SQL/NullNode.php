<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

/**
 * Represents an IS NULL or IS NOT NULL operation in SQL WHERE clause.
 *
 * Tests whether a field contains a null value. This is distinct from
 * equality comparison because SQL null comparison semantics require
 * the IS NULL syntax rather than = NULL.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class NullNode extends SqlNode
{
    /**
     * Create a new IS NULL/IS NOT NULL operation node.
     *
     * @param SqlNode $field   Field reference to test for null value. Typically a FieldNode
     *                         representing a database column that will be checked to determine
     *                         if it contains a SQL NULL value.
     * @param bool    $negated When true, produces an IS NOT NULL operation that checks the field
     *                         is not null. When false (default), produces an IS NULL operation
     *                         that checks the field is null. Defaults to false.
     */
    public function __construct(
        public SqlNode $field,
        public bool $negated = false,
    ) {}
}
