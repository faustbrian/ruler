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
 * Multiplication arithmetic operator.
 *
 * Multiplies the left operand by the right operand. Both operands must resolve
 * to numeric values. The result is the product of the two operands (left * right).
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Multiplication extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the multiplication result of the left and right operands.
     *
     * @param  Context $context Evaluation context for resolving operands
     * @return Value   Value object containing the product of the multiplication operation
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->multiply($right->prepareValue($context)));
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
