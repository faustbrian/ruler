<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Mathematical;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;
use RuntimeException;

use function abs;
use function is_numeric;
use function throw_unless;

/**
 * Absolute value operator for computing the non-negative magnitude of numeric values.
 *
 * Calculates the absolute value of its operand, returning the distance from zero without
 * regard to sign. For positive numbers, returns the number unchanged; for negative numbers,
 * returns the positive equivalent. Throws an exception if the operand does not resolve to
 * a numeric value.
 *
 * ```php
 * $abs = new Abs();
 * $abs->addOperand(new Variable('temperature'));
 * $value = $abs->prepareValue($context); // returns non-negative value
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Abs extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the absolute value result for the operand.
     *
     * Evaluates the single operand within the provided context and computes its absolute
     * value using PHP's built-in abs() function. Validates that the resolved operand is
     * numeric before performing the calculation.
     *
     * @param Context $context Evaluation context containing variable values and facts
     *                         required for operand resolution
     *
     * @throws RuntimeException When the operand value is not numeric
     *
     * @return Value Value object containing the absolute value of the operand
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        throw_unless(is_numeric($value), RuntimeException::class, 'Abs: value must be numeric');

        /** @var float|int $value */
        return new Value(abs($value));
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Unary cardinality indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
