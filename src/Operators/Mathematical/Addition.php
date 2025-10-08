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
 * @author Brian Faust <brian@cline.sh>
 */
final class Addition extends VariableOperator implements VariableOperand
{
    /**
     * Prepare the sum of two operands.
     *
     * Evaluates both operands in the given context and returns their sum.
     * Delegates the actual addition logic to the Value::add() method.
     *
     * @param  Context $context The evaluation context containing facts and variables
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
     * Get the required number of operands for this operator.
     *
     * @return OperandCardinality Returns BINARY constant indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
