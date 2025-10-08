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

/**
 * Absolute value operator for computing the non-negative magnitude of numeric values.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Abs extends VariableOperator implements VariableOperand
{
    /**
     * Prepare the absolute value result for the operand.
     *
     * Evaluates the single operand and returns its absolute (non-negative) value.
     * The operand must resolve to a numeric value or an exception is thrown.
     *
     * @param Context $context The evaluation context containing facts and variables
     *
     * @throws RuntimeException If the operand value is not numeric
     *
     * @return Value Value object containing the absolute value of the operand
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        if (!is_numeric($value)) {
            throw new RuntimeException('Abs: value must be numeric');
        }

        /** @var float|int $value */
        return new Value(abs($value));
    }

    /**
     * Get the required number of operands for this operator.
     *
     * @return OperandCardinality Returns UNARY constant indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
