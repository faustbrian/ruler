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
 * Logical NAND (NOT AND) operator that returns true unless all operands are true.
 *
 * Implements boolean NAND (¬∧), the negation of logical AND. Returns true if at
 * least one operand evaluates to false, and returns false only when all operands
 * are true. Uses short-circuit evaluation for performance, returning true immediately
 * upon encountering the first false operand. Requires at least two operands and
 * follows standard NAND truth table logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNand extends LogicalOperator
{
    /**
     * Evaluates the NAND logic over all proposition operands.
     *
     * @param  Context $context Execution context containing variable values for operand evaluation
     * @return bool    True if any operand is false, false only if all operands are true
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
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Multiple cardinality (two or more operands required)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
