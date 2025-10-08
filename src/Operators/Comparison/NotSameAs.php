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
 * Evaluates whether two values are not identical in type and value.
 *
 * Performs strict inequality comparison (not same as) between operands,
 * checking both type and value identity. Returns true when operands differ
 * in either type or value.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NotSameAs extends VariableOperator implements Proposition
{
    /**
     * Evaluates the not-same-as comparison between two operands.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for evaluating this proposition
     * @return bool    True when operands are not identical in type and value,
     *                 false when they are strictly identical
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->sameAs($right->prepareValue($context)) === false;
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
