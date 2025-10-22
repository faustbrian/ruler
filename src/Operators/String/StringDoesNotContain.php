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
 * Evaluates whether a string does not contain a substring using case-sensitive comparison.
 *
 * Performs case-sensitive substring matching by checking if the left operand
 * does not contain the right operand anywhere within it. Character case must
 * match exactly, treating 'A' and 'a' as distinct characters.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringDoesNotContain extends VariableOperator implements Proposition
{
    /**
     * Evaluates the case-sensitive string non-containment comparison.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule execution
     * @return bool    True if the left operand does not contain the right operand (case-sensitive),
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
     * Returns the required number of operands for this operator.
     *
     * @return OperandCardinality Binary cardinality constant indicating two operands required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
