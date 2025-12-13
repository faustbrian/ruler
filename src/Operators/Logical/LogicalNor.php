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
 * Logical NOR (NOT OR) operator that returns true only when all operands are false.
 *
 * Implements boolean NOR (¬∨), the negation of logical OR. Returns true only when
 * all operands evaluate to false, and returns false if at least one operand is true.
 * Uses short-circuit evaluation for performance, returning false immediately upon
 * encountering the first true operand. Requires at least two operands and follows
 * standard NOR truth table logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNor extends LogicalOperator
{
    /**
     * Evaluates the NOR logic over all proposition operands.
     *
     * @param  Context $context Execution context containing variable values for operand evaluation
     * @return bool    True if all operands are false, false if any operand is true
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
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Multiple cardinality (two or more operands required)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
