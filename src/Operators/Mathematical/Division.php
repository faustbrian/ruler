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
 * Division arithmetic operator for dividing one numeric value by another.
 *
 * Performs standard arithmetic division where the left operand (dividend) is divided
 * by the right operand (divisor). The operation is delegated to the Value object's
 * divide() method, which handles type coercion and division-by-zero validation.
 *
 * ```php
 * $division = new Division();
 * $division->addOperand(new Variable('total_cost'));
 * $division->addOperand(new Variable('quantity'));
 * $unit_price = $division->prepareValue($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Division extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the quotient of dividing the left operand by the right operand.
     *
     * Evaluates both operands within the provided context and computes their quotient.
     * The division operation is delegated to the Value object's divide() method, which
     * validates against division by zero and handles numeric type coercion.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
     * @return Value   Value object containing the quotient of left divided by right
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
     * @return OperandCardinality Binary cardinality indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
