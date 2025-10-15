<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SqlWhere;

/**
 * Represents a binary comparison operation in SQL WHERE clause.
 *
 * Encapsulates comparison expressions such as equality, inequality, and
 * relational operators (=, !=, <, >, <=, >=) applied to two operands.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ComparisonNode extends SqlNode
{
    /**
     * Create a new comparison node.
     *
     * @param string  $operator Comparison operator symbol (=, !=, <>, <, >, <=, >=).
     *                          The <> operator is treated as an alternative syntax for !=
     *                          in SQL standard compliance.
     * @param SqlNode $left     left-hand operand of the comparison, typically a field reference
     *                          or literal value that forms the left side of the binary expression
     * @param SqlNode $right    right-hand operand of the comparison, typically a literal value
     *                          or field reference that forms the right side of the expression
     */
    public function __construct(
        public string $operator,
        public SqlNode $left,
        public SqlNode $right,
    ) {}
}
