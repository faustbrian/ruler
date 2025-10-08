<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Set;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Values\Set;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Set union operator.
 *
 * Computes the union of multiple sets, combining all unique elements
 * from each operand set into a single result set. Accepts one or more
 * set operands and merges them without duplicates.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Union extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the union set value for evaluation.
     *
     * Iterates through all set operands and progressively merges them
     * into a single unified set containing all unique elements from
     * every input set. Duplicate elements appear only once in the result.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule evaluation
     * @return Value   Returns a Value object containing the union set with all
     *                 unique elements from all operand sets combined
     */
    public function prepareValue(Context $context): Value
    {
        $union = new Set([]);

        /** @var VariableOperand $operand */
        foreach ($this->getOperands() as $operand) {
            $set = $operand->prepareValue($context)->getSet();
            $union = $union->union($set);
        }

        return $union;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Returns MULTIPLE indicating this operator requires one or more operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
