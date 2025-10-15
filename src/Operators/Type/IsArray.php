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

use function is_array;

/**
 * Validates that a value is a PHP array using strict type checking.
 *
 * Performs strict type checking using is_array() to determine if the operand
 * is a native PHP array. Returns true for both indexed and associative arrays,
 * but false for objects implementing ArrayAccess.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsArray extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the operand is a PHP array.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule execution
     * @return bool    True if the value is an array, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        return is_array($value);
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
