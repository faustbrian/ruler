<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Logical;

use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\PropositionOperator;

/**
 * Abstract base class for logical operators that combine multiple propositions.
 *
 * Provides common functionality for logical operators (AND, OR, NOT, NAND, NOR, XOR)
 * that evaluate multiple proposition operands according to boolean logic rules.
 * Extends PropositionOperator to inherit operand management capabilities and
 * implements Proposition to allow logical operators to be nested and composed.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class LogicalOperator extends PropositionOperator implements Proposition
{
    /**
     * Creates a new logical operator with the specified proposition operands.
     *
     * Accepts an array of propositions and adds each as an operand to this
     * logical operator. The number and type of propositions accepted depends
     * on the specific logical operator implementation (e.g., NOT requires one
     * operand, AND/OR require two or more).
     *
     * @param array<Proposition> $props Array of proposition operands to be evaluated
     *                                  by this logical operator according to its
     *                                  specific boolean logic rules
     */
    public function __construct(array $props = [])
    {
        foreach ($props as $operand) {
            $this->addOperand($operand);
        }
    }
}
