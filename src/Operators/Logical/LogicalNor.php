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
 * Logical NOR (NOT OR) operator that returns true only when all operands evaluate to false.
 *
 * Evaluates multiple proposition operands and returns the negation of a logical OR
 * operation. Returns true only when all operands evaluate to false, and returns
 * false if at least one operand evaluates to true. Uses short-circuit evaluation,
 * returning false immediately upon encountering the first true operand.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNor extends LogicalOperator
{
    /**
     * Evaluates whether all operands return false.
     *
     * Iterates through all proposition operands and evaluates each within the
     * provided context. Returns false immediately upon encountering the first
     * true operand (short-circuit evaluation). Returns true only if all operands
     * evaluate to false, effectively negating logical OR behavior.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate all proposition operands
     * @return bool    True if all operands evaluate to false, false if any operand is true
     */
    public function evaluate(Context $context): bool
    {
        /** @var array<Proposition> $operands */
        $operands = $this->getOperands();

        foreach ($operands as $operand) {
            if ($operand->evaluate($context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Logical NOR accepts multiple operands (two or more propositions).
     *
     * @return OperandCardinality Multiple cardinality constant indicating variable number of operands accepted
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
