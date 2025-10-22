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
 * Case-insensitive string suffix test operator.
 *
 * Tests whether a string value ends with a specified suffix using case-insensitive
 * comparison. The comparison ignores character case differences, treating uppercase
 * and lowercase letters as equivalent, and returns true when the value terminates
 * with the suffix regardless of case variations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class EndsWithInsensitive extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the string ends with the suffix (case-insensitive).
     *
     * Resolves both operands from context and performs case-insensitive suffix
     * matching to determine if the left operand string terminates with the
     * right operand substring, ignoring character case differences.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for resolving operands during rule evaluation
     * @return bool    True when the left operand value ends with the right operand
     *                 suffix (case-insensitive), false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->endsWith($right->prepareValue($context), true);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality requiring exactly two operands
     *                            (string value and suffix to test)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
