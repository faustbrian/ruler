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
 * Logical NAND (NOT AND) operator that returns true unless all operands evaluate to true.
 *
 * Evaluates multiple proposition operands and returns the negation of a logical AND
 * operation. Returns true if at least one operand evaluates to false, and returns
 * false only when all operands evaluate to true. Uses short-circuit evaluation,
 * returning true immediately upon encountering the first false operand.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNand extends LogicalOperator
{
    /**
     * Evaluates whether at least one operand returns false.
     *
     * Iterates through all proposition operands and evaluates each within the
     * provided context. Returns true immediately upon encountering the first
     * false operand (short-circuit evaluation). Returns false only if all
     * operands evaluate to true, effectively negating logical AND behavior.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate all proposition operands
     * @return bool    True if any operand evaluates to false, false only if all operands are true
     */
    public function evaluate(Context $context): bool
    {
        /** @var array<Proposition> $operands */
        $operands = $this->getOperands();

        foreach ($operands as $operand) {
            if (!$operand->evaluate($context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Logical NAND accepts multiple operands (two or more propositions).
     *
     * @return OperandCardinality Multiple cardinality constant indicating variable number of operands accepted
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
