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
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Set union operator.
 *
 * Computes the union of multiple sets, combining all unique elements
 * from each operand set into a single result set. Accepts one or more
 * set operands and merges them without duplicates, following standard
 * mathematical set union semantics.
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
     * every input set. Duplicate elements across sets appear only once
     * in the final result, maintaining set uniqueness invariants.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule evaluation
     * @return Value   Value object containing the union set with all unique
     *                 elements from all operand sets combined
     */
    public function prepareValue(Context $context): Value
    {
        $union = null;

        /** @var VariableOperand $operand */
        foreach ($this->getOperands() as $operand) {
            $set = $operand->prepareValue($context)->getSet();
            $union = null === $union ? $set : $union->union($set->asValue());
        }

        return $union?->asValue() ?? new Value([]);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Multiple cardinality allowing one or more set operands
     *                            to be combined into a single union
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
