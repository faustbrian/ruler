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
 * Computes the intersection of multiple sets.
 *
 * Evaluates multiple set operands and returns a new set containing only the
 * elements that exist in all provided sets. The intersection operation is
 * performed sequentially across all operands, progressively narrowing down
 * to common elements.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Intersect extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the intersection value by combining all operand sets.
     *
     * Iterates through all operands, converting each to a Set and computing
     * the progressive intersection. The first operand initializes the result,
     * and subsequent operands are intersected sequentially.
     *
     * @param  Context $context Evaluation context providing variable values and state
     *                          for resolving operand values during rule execution
     * @return Set     The resulting set containing only elements present in all operand sets
     */
    public function prepareValue(Context $context): Value
    {
        $intersect = null;

        /** @var VariableOperand $operand */
        foreach ($this->getOperands() as $operand) {
            if (!$intersect instanceof Set) {
                $intersect = $operand->prepareValue($context)->getSet();
            } else {
                $set = $operand->prepareValue($context)->getSet();
                $intersect = $intersect->intersect($set);
            }
        }

        /** @var Set $intersect */
        return $intersect;
    }

    /**
     * Returns the required number of operands for this operator.
     *
     * @return OperandCardinality Multiple cardinality constant indicating two or more operands required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
