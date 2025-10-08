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
 * Evaluates whether one value is greater than or equal to another.
 *
 * This operator performs a greater-than-or-equal-to comparison between the left
 * and right operands after resolving them within the given context. The implementation
 * uses the inverse of the lessThan comparison to determine if the left operand is
 * greater than or equal to the right operand.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class GreaterThanOrEqualTo extends VariableOperator implements Proposition
{
    /**
     * Evaluates the greater-than-or-equal-to comparison between left and right operands.
     *
     * @param  Context $context Context containing variables and values for operand resolution
     * @return bool    True if the left operand value is greater than or equal to the right operand value
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->lessThan($right->prepareValue($context)) === false;
    }

    /**
     * Returns the number of operands required by this operator.
     *
     * @return OperandCardinality Binary operator requiring exactly two operands for comparison
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
