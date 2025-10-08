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
 * Case-sensitive string containment check operator.
 *
 * Evaluates whether the left operand does NOT contain the right operand
 * as a substring. Uses case-sensitive comparison for string matching.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringDoesNotContain extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand does not contain the right operand.
     *
     * Performs case-sensitive substring comparison by checking if the left
     * operand string does not contain the right operand string anywhere
     * within its content.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule evaluation
     * @return bool    Returns true if left operand does not contain right operand,
     *                 false if the substring is found
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->stringContains($right->prepareValue($context)) === false;
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
