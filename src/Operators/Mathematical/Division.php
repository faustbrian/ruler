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
 * Divides the left operand by the right operand.
 *
 * Performs arithmetic division where the left operand (dividend) is
 * divided by the right operand (divisor). This operator implements
 * VariableOperand to allow chaining with other mathematical operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Division extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the quotient of dividing the left operand by the right operand.
     *
     * @param  Context $context Context containing variable values for operand resolution
     * @return Value   Value object containing the result of left divided by right
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->divide($right->prepareValue($context)));
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Binary cardinality (requires exactly two operands: dividend and divisor)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
