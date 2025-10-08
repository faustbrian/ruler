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
 * Logical OR operator that returns true when at least one operand evaluates to true.
 *
 * Evaluates multiple proposition operands and returns true if any operand evaluates
 * to true within the given context. Uses short-circuit evaluation, stopping and
 * returning true at the first true operand encountered for performance optimization.
 * Returns false only when all operands evaluate to false. Accepts two or more
 * operands and follows standard boolean OR logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalOr extends LogicalOperator
{
    /**
     * Evaluates whether at least one operand returns true.
     *
     * Iterates through all proposition operands and evaluates each within the
     * provided context. Returns true immediately upon encountering the first
     * true operand (short-circuit evaluation). Returns false only if all
     * operands evaluate to false.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate all proposition operands
     * @return bool    True if any operand evaluates to true, false if all operands are false
     */
    public function evaluate(Context $context): bool
    {
        /** @var Proposition $operand */
        foreach ($this->getOperands() as $operand) {
            if ($operand->evaluate($context) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Logical OR accepts multiple operands (two or more propositions).
     *
     * @return OperandCardinality Multiple cardinality constant indicating variable number of operands accepted
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
