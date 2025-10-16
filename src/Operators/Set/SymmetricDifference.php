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
 * Set symmetric difference operator.
 *
 * Computes the symmetric difference between two sets, returning elements
 * that appear in either the left or right set, but not in both. This is
 * equivalent to (A ∪ B) - (A ∩ B) in set theory, producing the set of
 * elements that belong to exactly one of the input sets.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SymmetricDifference extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the symmetric difference set value for evaluation.
     *
     * Resolves both set operands from context and computes their symmetric
     * difference by identifying elements unique to each set while excluding
     * common elements shared by both sets.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule evaluation
     * @return Value   Value object containing the symmetric difference set with
     *                 elements that exist in exactly one of the two input sets
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->getSet()
            ->symmetricDifference($right->prepareValue($context)->getSet());
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality requiring exactly two set operands
     *                            for computing the symmetric difference
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
