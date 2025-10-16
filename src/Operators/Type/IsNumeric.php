<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Type;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

use function is_numeric;

/**
 * Validates that a value is numeric or a numeric string.
 *
 * Uses PHP's is_numeric() to determine if a value can be interpreted as a number.
 * Returns true for integers, floats, numeric strings like "123" or "45.67", and
 * values in scientific notation. Accepts both actual numbers and string representations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsNumeric extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the operand is numeric or a numeric string.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule execution
     * @return bool    True if the value is numeric or a numeric string, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        return is_numeric($value);
    }

    /**
     * Returns the required number of operands for this operator.
     *
     * @return OperandCardinality Unary cardinality constant indicating one operand required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
