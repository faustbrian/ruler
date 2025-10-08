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
 * Performs exponentiation by raising a base value to a specified power.
 *
 * This operator calculates the result of raising the left operand (base) to
 * the power of the right operand (exponent). The computation is delegated to
 * the Value object's exponentiate method and returns a new Value containing
 * the calculated result.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Exponentiate extends VariableOperator implements VariableOperand
{
    /**
     * Resolves operands and calculates the exponentiation result.
     *
     * @param  Context $context Context containing variables and values for operand resolution
     * @return Value   New Value object containing the result of base raised to exponent power
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return new Value($left->prepareValue($context)->exponentiate($right->prepareValue($context)));
    }

    /**
     * Returns the number of operands required by this operator.
     *
     * @return OperandCardinality Binary operator requiring exactly two operands (base and exponent)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
