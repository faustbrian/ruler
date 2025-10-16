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
 * Computes the set complement of multiple set operands.
 *
 * Performs a relative complement operation across multiple sets, where
 * each subsequent set's elements are removed from the accumulated result.
 * The first operand establishes the initial set, and each following operand
 * subtracts its elements from the accumulated complement.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Complement extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the complement of multiple sets for evaluation.
     *
     * Iterates through operands, using the first as the base set and
     * computing the relative complement with each subsequent set. The
     * result contains elements from the first set that are not present
     * in any of the following sets.
     *
     * @param  Context $context Context containing variable values for operand resolution
     * @return Set     Set object containing the resulting complement
     */
    public function prepareValue(Context $context): Value
    {
        $complement = null;

        /** @var VariableOperand $operand */
        foreach ($this->getOperands() as $operand) {
            if (!$complement instanceof Set) {
                $complement = $operand->prepareValue($context)->getSet();
            } else {
                $set = $operand->prepareValue($context)->getSet();
                $complement = $complement->complement($set);
            }
        }

        /** @var Set $complement */
        return $complement;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Multiple cardinality (requires two or more set operands)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
