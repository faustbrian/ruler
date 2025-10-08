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
 * Evaluates whether a set contains a specific value.
 *
 * Checks if the left operand (a set/array) contains the right operand value.
 * Uses set membership testing to determine if the value exists within the
 * collection.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SetContains extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the set contains the target value.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for evaluating this proposition
     * @return bool    True when the left operand set contains the right operand value,
     *                 false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->getSet()->setContains($right->prepareValue($context));
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality constant requiring exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
