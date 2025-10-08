<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\String;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Evaluates whether a string value ends with a specified suffix (case-sensitive).
 *
 * This operator performs case-sensitive string suffix matching by comparing
 * the left operand value against the right operand suffix. The comparison
 * respects character case and returns true only when the value ends with
 * the exact suffix string.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EndsWith extends VariableOperator implements Proposition
{
    /**
     * Evaluates the case-sensitive suffix comparison between left and right operands.
     *
     * @param  Context $context Context containing variables and values for operand resolution
     * @return bool    True if the left operand value ends with the right operand suffix
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->endsWith($right->prepareValue($context));
    }

    /**
     * Returns the number of operands required by this operator.
     *
     * @return OperandCardinality Binary operator requiring exactly two operands (value and suffix)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
