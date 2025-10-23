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
 * Evaluates whether one value is strictly greater than another.
 *
 * Performs a greater-than comparison between the left and right operands
 * after resolving them within the given context. The comparison is delegated
 * to the Value object's greaterThan method, which implements type-specific
 * comparison logic for different value types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class GreaterThan extends VariableOperator implements Proposition
{
    /**
     * Evaluates the greater-than comparison between left and right operands.
     *
     * @param  Context $context Execution context providing variable values for operand resolution
     * @return bool    True if the left operand value is strictly greater than the right operand value
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->greaterThan($right->prepareValue($context));
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
