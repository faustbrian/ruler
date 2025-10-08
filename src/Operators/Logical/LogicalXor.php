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
 * Logical XOR (exclusive OR) operator that returns true when exactly one operand evaluates to true.
 *
 * Evaluates multiple proposition operands and returns true if and only if exactly
 * one operand evaluates to true within the given context. Returns false if zero
 * operands are true or if more than one operand is true. This implements exclusive
 * OR logic where the result is true only for singular truth. Accepts two or more
 * operands and evaluates all operands to count how many are true.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalXor extends LogicalOperator
{
    /**
     * Evaluates whether exactly one operand returns true.
     *
     * Iterates through all proposition operands and evaluates each within the
     * provided context, counting how many evaluate to true. Uses short-circuit
     * evaluation to return false immediately if more than one true operand is
     * found. Returns true only if exactly one operand evaluates to true.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate all proposition operands
     * @return bool    True if exactly one operand evaluates to true, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        $true = 0;

        /** @var Proposition $operand */
        foreach ($this->getOperands() as $operand) {
            if (true === $operand->evaluate($context)) {
                if (++$true > 1) {
                    return false;
                }
            }
        }

        return $true === 1;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Logical XOR accepts multiple operands (two or more propositions).
     *
     * @return OperandCardinality Multiple cardinality constant indicating variable number of operands accepted
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
