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
 * Rounds a numeric value up to the nearest integer.
 *
 * Applies the mathematical ceiling function to its operand, returning
 * the smallest integer value greater than or equal to the input. This
 * operator implements VariableOperand to allow chaining with other
 * mathematical operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Ceil extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the ceiling value of the operand for evaluation.
     *
     * @param  Context $context Context containing variable values for operand resolution
     * @return Value   Value object containing the ceiling of the operand's resolved value
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->ceil());
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Unary cardinality (requires exactly one operand)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
