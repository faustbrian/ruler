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
use RuntimeException;

use function in_array;
use function is_array;

/**
 * Not in array membership operator.
 *
 * Checks whether a value is not present in an array. The left operand is the
 * value to search for, and the right operand must be an array. Uses strict
 * comparison to ensure type-safe matching.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NotIn extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand value is not present in the right operand array.
     *
     * @param Context $context Context with which to evaluate this Proposition
     *
     * @throws RuntimeException If the right operand is not an array
     *
     * @return bool Returns true if the value is not in the array, false if it is found
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $value = $left->prepareValue($context)->getValue();
        $array = $right->prepareValue($context)->getValue();

        if (!is_array($array)) {
            throw new RuntimeException('NotIn: second operand must be an array');
        }

        return !in_array($value, $array, true);
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
