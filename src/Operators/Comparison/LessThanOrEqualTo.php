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
 * Less than or equal to comparison operator for evaluating whether one value is less than or equal to another.
 *
 * Compares two variable operands by evaluating their values within a given context
 * and determining if the left operand is less than or equal to the right operand.
 * This operator expects exactly two operands and implements the comparison by
 * negating the greater-than check (i.e., NOT greater than equals less than or equal).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LessThanOrEqualTo extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand is less than or equal to the right operand.
     *
     * Retrieves both operands, prepares their values using the provided context,
     * and performs the comparison. The implementation uses inverse logic by checking
     * if the left value is NOT greater than the right value, which is mathematically
     * equivalent to less than or equal to.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to resolve and compare the operands
     * @return bool    True if left operand is less than or equal to right operand, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->greaterThan($right->prepareValue($context)) === false;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Less than or equal comparison requires exactly two operands (binary operation).
     *
     * @return OperandCardinality Binary cardinality constant indicating two operands required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
