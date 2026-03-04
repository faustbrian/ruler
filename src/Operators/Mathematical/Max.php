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
 * Maximum value extraction operator for finding the highest value in a collection.
 *
 * Extracts and returns the maximum value from a collection or set. The operand must
 * resolve to a Value containing a set of comparable values. The operation delegates
 * to the set's max() method to identify the highest value.
 *
 * ```php
 * $max = new Max();
 * $max->addOperand(new Variable('prices')); // prices is a collection
 * $highest = $max->prepareValue($context); // returns highest price
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Max extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the maximum value from the operand's collection.
     *
     * Evaluates the operand within the provided context to resolve its value, extracts
     * the set from that value, and returns the maximum value found in the set.
     *
     * @param  Context $context Evaluation context containing variable values and facts
     *                          required for operand resolution
     * @return Value   Value object containing the maximum value from the collection
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->getSet()->max());
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
