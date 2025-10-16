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
use function throw_unless;

/**
 * Checks if a value exists within an array using strict comparison.
 *
 * Evaluates whether the left operand's value is present in the right operand's
 * array. Uses strict type comparison (===) to ensure type safety when matching
 * values, preventing loose comparison issues like 0 == "string".
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class In extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand exists in the right operand array.
     *
     * @param Context $context Execution context providing variable values for operand resolution
     *
     * @throws RuntimeException When the right operand does not evaluate to an array type
     *
     * @return bool True if the value exists in the array using strict comparison, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $value = $left->prepareValue($context)->getValue();
        $array = $right->prepareValue($context)->getValue();

        throw_unless(is_array($array), RuntimeException::class, 'In: second operand must be an array');

        return in_array($value, $array, true);
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Binary cardinality requiring exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
