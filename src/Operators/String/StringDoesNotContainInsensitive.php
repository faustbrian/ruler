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
 * Case-insensitive string containment check operator.
 *
 * Evaluates whether the left operand does NOT contain the right operand
 * as a substring. Uses case-insensitive comparison for string matching,
 * treating 'A' and 'a' as equivalent characters.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringDoesNotContainInsensitive extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand does not contain the right operand.
     *
     * Performs case-insensitive substring comparison by checking if the left
     * operand string does not contain the right operand string anywhere
     * within its content, ignoring character case differences.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule evaluation
     * @return bool    Returns true if left operand does not contain right operand,
     *                 false if the substring is found (case-insensitive)
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->stringContainsInsensitive($right->prepareValue($context)) === false;
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
