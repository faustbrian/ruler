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
 * Evaluates whether two values are not equal using type-safe comparison.
 *
 * Performs inequality comparison between the left and right operands after
 * resolving them within the given context. Returns true when values differ
 * and false when they match, using the inverse of the equalTo comparison.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NotEqualTo extends VariableOperator implements Proposition
{
    /**
     * Evaluates the inequality comparison between left and right operands.
     *
     * @param  Context $context Execution context providing variable values for operand resolution
     * @return bool    True if the left operand value is not equal to the right operand value
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
     * @return OperandCardinality Binary cardinality requiring exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
