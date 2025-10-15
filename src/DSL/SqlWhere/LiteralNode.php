<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SqlWhere;

/**
 * Represents a literal value in SQL WHERE clause.
 *
 * Encapsulates constant values such as strings, numbers, booleans,
 * or null that appear directly in SQL expressions. These are typically
 * used as comparison operands or list elements.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LiteralNode extends SqlNode
{
    /**
     * Create a new literal value node.
     *
     * @param mixed $value Constant value to be used in the expression. Can be any scalar type
     *                     (string, int, float, bool) or null. Strings are extracted from quoted
     *                     literals in the SQL source, numbers are parsed from numeric tokens,
     *                     and boolean/null values come from SQL keywords (TRUE, FALSE, NULL).
     */
    public function __construct(
        public mixed $value,
    ) {}
}
