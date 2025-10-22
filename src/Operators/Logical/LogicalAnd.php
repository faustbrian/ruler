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

use function array_all;

/**
 * Logical AND operator that returns true only when all operands are true.
 *
 * Implements boolean conjunction (∧) over multiple propositions. Returns true
 * if and only if every operand evaluates to true. Uses short-circuit evaluation
 * for performance, stopping immediately upon encountering the first false operand.
 * Requires at least two operands and follows standard boolean AND truth table logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalAnd extends LogicalOperator
{
    /**
     * Evaluates whether all proposition operands are true.
     *
     * @param  Context $context Execution context containing variable values for operand evaluation
     * @return bool    True if all operands evaluate to true, false if any operand is false
     */
    public function evaluate(Context $context): bool
    {
        return array_all($this->getOperands(), fn ($operand): bool => $operand->evaluate($context));
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
