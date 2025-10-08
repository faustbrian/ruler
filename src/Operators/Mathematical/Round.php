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

use function array_key_exists;
use function is_numeric;
use function round;

/**
 * Rounds numeric values to specified precision.
 *
 * Accepts one or two operands: a required numeric value and an optional
 * precision (defaults to 0 for integer rounding). Validates that both
 * operands are numeric before performing the rounding operation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Round extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the rounded value for use in rule evaluation.
     *
     * Extracts numeric value and optional precision from operands, validates
     * both are numeric, then performs PHP's round() function with the specified
     * precision (defaulting to 0).
     *
     * @param Context $context Runtime context containing variable values and state
     *                         for resolving operand values
     *
     * @throws RuntimeException When the value operand is not numeric or when
     *                          the precision operand is provided but not numeric
     *
     * @return Value Wrapped rounded numeric value ready for evaluation
     */
    public function prepareValue(Context $context): Value
    {
        $operands = $this->getOperands();

        /** @var VariableOperand $valueOperand */
        $valueOperand = $operands[0];

        $value = $valueOperand->prepareValue($context)->getValue();

        if (!is_numeric($value)) {
            throw new RuntimeException('Round: value must be numeric');
        }

        $precision = 0;

        if (array_key_exists(1, $operands)) {
            /** @var VariableOperand $precisionOperand */
            $precisionOperand = $operands[1];
            $precision = $precisionOperand->prepareValue($context)->getValue();

            if (!is_numeric($precision)) {
                throw new RuntimeException('Round: precision must be numeric');
            }
        }

        return new Value(round((float) $value, (int) $precision));
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Multiple cardinality constant allowing one or more operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
