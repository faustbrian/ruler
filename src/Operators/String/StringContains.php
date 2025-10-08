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
 * Evaluates whether a string contains a specific substring.
 *
 * Performs case-sensitive substring matching to determine if the left
 * operand string contains the right operand substring anywhere within it.
 * Comparison respects character case.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringContains extends VariableOperator implements Proposition
{
    /**
     * Evaluates the case-sensitive string-contains comparison.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for evaluating this proposition
     * @return bool    True when the left operand contains the right operand substring
     *                 (case-sensitive), false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->stringContains($right->prepareValue($context));
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
