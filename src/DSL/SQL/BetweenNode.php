<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

/**
 * Represents a BETWEEN operation in SQL WHERE clause.
 *
 * Models the SQL BETWEEN operator for range checking, which tests whether
 * a field value falls within an inclusive range. Maps to SQL syntax like
 * "field BETWEEN min AND max" where both boundaries are inclusive.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class BetweenNode extends SqlNode
{
    /**
     * Create a new BETWEEN operation node.
     *
     * @param SqlNode $field Field or expression node to test against the range. Typically
     *                       a FieldNode representing a column name, but can be any SQL
     *                       expression that produces a comparable value.
     * @param SqlNode $min   Minimum boundary node representing the lower bound of the range
     *                       (inclusive). Usually a ValueNode but can be a computed expression
     *                       or column reference for dynamic range checks.
     * @param SqlNode $max   Maximum boundary node representing the upper bound of the range
     *                       (inclusive). Like min, typically a ValueNode but supports any
     *                       expression that produces a comparable value.
     */
    public function __construct(
        public SqlNode $field,
        public SqlNode $min,
        public SqlNode $max,
    ) {}
}
