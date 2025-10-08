<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Logical;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;

/**
 * Logical AND operator that returns true only when all operands evaluate to true.
 *
 * Evaluates multiple proposition operands and returns true if and only if every
 * operand evaluates to true within the given context. Uses short-circuit evaluation,
 * stopping at the first false operand encountered for performance optimization.
 * Accepts two or more operands and follows standard boolean AND logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalAnd extends LogicalOperator
{
    /**
     * Evaluates whether all operands return true.
     *
     * Iterates through all proposition operands and evaluates each within the
     * provided context. Returns false immediately upon encountering the first
     * false operand (short-circuit evaluation). If all operands evaluate to true,
     * returns true.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate all proposition operands
     * @return bool    True if all operands evaluate to true, false if any operand is false
     */
    public function evaluate(Context $context): bool
    {
        /** @var Proposition $operand */
        foreach ($this->getOperands() as $operand) {
            if ($operand->evaluate($context) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Logical AND accepts multiple operands (two or more propositions).
     *
     * @return OperandCardinality Multiple cardinality constant indicating variable number of operands accepted
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
