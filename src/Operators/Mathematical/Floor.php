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
 * Rounds a numeric value down to the nearest integer using the floor function.
 *
 * Applies the mathematical floor operation to its operand, returning the largest integer
 * less than or equal to the input value. For example, floor(4.7) returns 4, floor(-4.3)
 * returns -5. The operation is delegated to the Value object's floor() method.
 *
 * ```php
 * $floor = new Floor();
 * $floor->addOperand(new Variable('amount'));
 * $rounded = $floor->prepareValue($context); // rounds down to nearest integer
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Floor extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the floor value of the operand.
     *
     * Evaluates the operand within the provided context and applies the floor function
     * to round down to the nearest integer. The operation is delegated to the Value
     * object's floor() method.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
     * @return Value   Value object containing the floor of the operand's value
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->floor());
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Unary cardinality indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
