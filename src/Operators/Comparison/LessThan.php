<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Comparison;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Less than comparison operator for evaluating whether one value is strictly less than another.
 *
 * Compares two variable operands by evaluating their values within a given context
 * and determining if the left operand is less than the right operand. This operator
 * expects exactly two operands and delegates the actual comparison logic to the
 * value objects returned by the operands.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LessThan extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand is less than the right operand.
     *
     * Retrieves both operands, prepares their values using the provided context,
     * and performs a less-than comparison. The comparison is delegated to the
     * value objects' lessThan method, which handles type-specific comparison logic.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to resolve and compare the operands
     * @return bool    True if left operand is strictly less than right operand, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->lessThan($right->prepareValue($context));
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Less than comparison requires exactly two operands (binary operation).
     *
     * @return OperandCardinality Binary cardinality constant indicating two operands required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
