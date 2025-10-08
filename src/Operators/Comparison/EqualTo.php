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
 * Evaluates whether two values are equal using type-safe comparison.
 *
 * This operator performs equality comparison between the left and right operands
 * after resolving them within the given context. The comparison is delegated to
 * the Value object's equalTo method, which implements the specific equality logic
 * for different value types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EqualTo extends VariableOperator implements Proposition
{
    /**
     * Evaluates the equality comparison between left and right operands.
     *
     * @param  Context $context Context containing variables and values for operand resolution
     * @return bool    True if the left operand value equals the right operand value
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->equalTo($right->prepareValue($context));
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
