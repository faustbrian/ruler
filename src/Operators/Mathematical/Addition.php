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
 * Addition arithmetic operator for combining two numeric values.
 *
 * Performs standard arithmetic addition by summing the left and right operands. Both
 * operands are evaluated within the provided context and their values are added together.
 * The actual addition operation is delegated to the Value object's add() method, which
 * handles type coercion and numeric operations.
 *
 * ```php
 * $addition = new Addition();
 * $addition->addOperand(new Variable('price'));
 * $addition->addOperand(new Variable('tax'));
 * $total = $addition->prepareValue($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Addition extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the sum of the left and right operands.
     *
     * Evaluates both operands within the provided context and computes their sum.
     * The addition operation is delegated to the Value object's add() method, which
     * handles numeric type coercion and arithmetic operations.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
     * @return Value   Value object containing the sum of the left and right operands
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->add($right->prepareValue($context)));
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Binary cardinality indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
