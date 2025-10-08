<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Mathematical;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Arithmetic subtraction operator.
 *
 * Performs subtraction of the right operand from the left operand,
 * computing the difference between two numeric values during rule
 * evaluation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Subtraction extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the subtraction result value for evaluation.
     *
     * Extracts both operands from the context and performs subtraction
     * by delegating to the Value object's subtract method, which handles
     * the numeric calculation.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule evaluation
     * @return Value   Returns a Value object containing the result of
     *                 left operand minus right operand
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->subtract($right->prepareValue($context)));
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Returns BINARY indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
