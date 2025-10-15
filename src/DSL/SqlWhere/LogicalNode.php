<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SqlWhere;

/**
 * Represents a logical operation (AND, OR, NOT) in SQL WHERE clause.
 *
 * Combines multiple conditions using boolean logic. AND requires all
 * operands to be true, OR requires at least one operand to be true,
 * and NOT inverts the truth value of its operand.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LogicalNode extends SqlNode
{
    /**
     * Create a new logical operation node.
     *
     * @param string              $operator Logical operator keyword: "AND", "OR", or "NOT" (uppercase).
     *                                      Determines how the operands are combined. AND requires all
     *                                      conditions to be true, OR requires any condition to be true,
     *                                      and NOT negates a single condition.
     * @param array<int, SqlNode> $operands Array of child nodes representing the conditions to combine.
     *                                      For AND and OR, typically contains 2+ operands. For NOT,
     *                                      typically contains a single operand to negate. Each operand
     *                                      can be any type of SqlNode including nested logical operations.
     */
    public function __construct(
        public string $operator,
        public array $operands,
    ) {}
}
