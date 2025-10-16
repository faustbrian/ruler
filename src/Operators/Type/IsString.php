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

use function is_string;

/**
 * Validates that a value is of type string.
 *
 * Performs strict type checking to determine if the operand evaluates to a PHP
 * string. Returns false for numeric values, objects with __toString methods, or
 * other types that might be convertible to strings but are not actual string types.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsString extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the operand is a PHP string.
     *
     * @param  Context $context evaluation context providing variable values and state
     *                          for resolving operand values during rule execution
     * @return bool    true if the value is a string, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        return is_string($value);
    }

    /**
     * Returns the required number of operands for this operator.
     *
     * @return OperandCardinality unary cardinality constant indicating one operand required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
