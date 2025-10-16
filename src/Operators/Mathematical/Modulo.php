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
 * Modulo arithmetic operator for calculating the remainder of division.
 *
 * Computes the remainder when dividing the left operand (dividend) by the right operand
 * (divisor). For example, 10 % 3 returns 1. The operation is delegated to the Value
 * object's modulo() method, which handles numeric type coercion.
 *
 * ```php
 * $modulo = new Modulo();
 * $modulo->addOperand(new Variable('total'));
 * $modulo->addOperand(new Variable('page_size'));
 * $remainder = $modulo->prepareValue($context); // total % page_size
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Modulo extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the remainder from dividing the left operand by the right operand.
     *
     * Evaluates both operands within the provided context and computes the modulo
     * operation. The calculation is delegated to the Value object's modulo() method,
     * which handles numeric type coercion.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
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
     * @return OperandCardinality Binary cardinality indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
