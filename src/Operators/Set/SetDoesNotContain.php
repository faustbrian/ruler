<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Set;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Set non-membership test operator.
 *
 * Tests whether a value is absent from a set by performing negated set membership
 * testing. Returns true when the left operand set does not contain the right
 * operand value, providing the inverse operation of set containment checking.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SetDoesNotContain extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the set does not contain the target value.
     *
     * Retrieves both operands from context, converts the left operand to a set,
     * and checks whether it lacks the right operand value using strict comparison.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for resolving operands during rule evaluation
     * @return bool    True when the left operand set does not contain the right
     *                 operand value, false when the value exists in the set
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->getSet()->setContains($right->prepareValue($context)) === false;
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality requiring exactly two operands
     *                            (set and value to test for absence)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
