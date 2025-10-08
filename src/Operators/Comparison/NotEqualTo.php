<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Comparison;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Not equal to comparison operator.
 *
 * Evaluates whether two operands are not equal to each other. This is the
 * inverse of the equality operator, returning true when values differ and
 * false when they match.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NotEqualTo extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand is not equal to the right operand.
     *
     * @param  Context $context Context with which to evaluate this Proposition
     * @return bool    Returns true if the values are not equal, false if they are equal
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->equalTo($right->prepareValue($context)) === false;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Returns BINARY indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
