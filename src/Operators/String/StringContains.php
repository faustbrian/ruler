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
 * Case-sensitive substring containment test operator.
 *
 * Tests whether a string value contains a specified substring anywhere within it
 * using case-sensitive comparison. The comparison respects character case and
 * returns true when the substring is found at any position in the value string.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringContains extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the string contains the specified substring.
     *
     * Resolves both operands from context and performs case-sensitive substring
     * matching to determine if the left operand string contains the right operand
     * substring at any position within the value.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for resolving operands during rule evaluation
     * @return bool    True when the left operand value contains the right operand
     *                 substring (case-sensitive), false otherwise
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
     * @return OperandCardinality Binary cardinality requiring exactly two operands
     *                            (string value and substring to search for)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
