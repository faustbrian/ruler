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
 * Modulo arithmetic operator.
 *
 * Calculates the remainder of dividing the left operand by the right operand.
 * Both operands must resolve to numeric values. The result is the remainder
 * after division (left % right).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Modulo extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the modulo result of the left operand divided by the right operand.
     *
     * @param  Context $context Evaluation context for resolving operands
     * @return Value   Value object containing the remainder of the division operation
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->modulo($right->prepareValue($context)));
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
