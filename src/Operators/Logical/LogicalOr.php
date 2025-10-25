<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Logical;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Enums\OperandCardinality;

use function array_any;

/**
 * Logical OR operator that returns true when at least one operand is true.
 *
 * Implements boolean disjunction (∨) over multiple propositions. Returns true if
 * any operand evaluates to true, and returns false only when all operands are false.
 * Uses short-circuit evaluation for performance, stopping immediately upon encountering
 * the first true operand. Requires at least two operands and follows standard boolean
 * OR truth table logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalOr extends LogicalOperator
{
    /**
     * Evaluates whether at least one proposition operand is true.
     *
     * @param  Context $context Execution context containing variable values for operand evaluation
     * @return bool    True if any operand is true, false if all operands are false
     */
    public function evaluate(Context $context): bool
    {
        return array_any($this->getOperands(), fn (mixed $operand): bool => $operand->evaluate($context));
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
