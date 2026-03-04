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
 * Exponentiation operator for raising a base value to a specified power.
 *
 * Calculates the result of raising the left operand (base) to the power of the right
 * operand (exponent). For example, with base 2 and exponent 3, returns 8 (2^3). The
 * computation is delegated to the Value object's exponentiate() method.
 *
 * ```php
 * $exp = new Exponentiate();
 * $exp->addOperand(new Variable('base'));
 * $exp->addOperand(new Variable('exponent'));
 * $result = $exp->prepareValue($context); // base^exponent
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Exponentiate extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the exponentiation result of raising base to the power of exponent.
     *
     * Evaluates both operands within the provided context and computes the result of
     * raising the base to the specified power. The exponentiation operation is delegated
     * to the Value object's exponentiate() method.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
     * @return Value   Value object containing the result of base raised to exponent power
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->exponentiate($right->prepareValue($context)));
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
