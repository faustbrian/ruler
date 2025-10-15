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
 * Evaluates strict equality between two values using type-safe comparison.
 *
 * Performs strict identity comparison (===) between operands, requiring
 * both type and value to match exactly. This operator is stricter than
 * loose equality, rejecting type coercion. For example, the integer 1
 * and string "1" are NOT considered the same.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SameAs extends VariableOperator implements Proposition
{
    /**
     * Evaluates strict identity comparison between the two operands.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for resolving and evaluating operands
     * @return bool    True when operands are strictly identical in type and value,
     *                 false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->sameAs($right->prepareValue($context));
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality (exactly two operands required)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
