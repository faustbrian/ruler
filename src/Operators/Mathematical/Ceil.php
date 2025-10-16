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
 * Rounds a numeric value up to the nearest integer using the ceiling function.
 *
 * Applies the mathematical ceiling operation to its operand, returning the smallest
 * integer greater than or equal to the input value. For example, ceil(4.3) returns 5,
 * ceil(-4.3) returns -4. The operation is delegated to the Value object's ceil() method.
 *
 * ```php
 * $ceil = new Ceil();
 * $ceil->addOperand(new Variable('amount'));
 * $rounded = $ceil->prepareValue($context); // rounds up to nearest integer
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Ceil extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the ceiling value of the operand.
     *
     * Evaluates the operand within the provided context and applies the ceiling function
     * to round up to the nearest integer. The operation is delegated to the Value object's
     * ceil() method.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
     * @return Value   Value object containing the ceiling of the operand's value
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
     * @return OperandCardinality Unary cardinality indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
