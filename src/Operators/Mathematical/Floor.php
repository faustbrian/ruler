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
 * Rounds a numeric value down to the nearest integer.
 *
 * This operator applies the mathematical floor function to round down a numeric
 * value to the largest integer less than or equal to the original value. The
 * computation is delegated to the Value object's floor method and returns a new
 * Value containing the rounded result.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Floor extends VariableOperator implements VariableOperand
{
    /**
     * Resolves the operand and calculates the floor result.
     *
     * @param  Context $context Context containing variables and values for operand resolution
     * @return Value   New Value object containing the floored numeric result
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->floor());
    }

    /**
     * Returns the number of operands required by this operator.
     *
     * @return OperandCardinality Unary operator requiring exactly one operand to floor
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
