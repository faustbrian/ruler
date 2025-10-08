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

/**
 * Validates that a value is strictly null.
 *
 * Performs strict null checking using type-safe comparison (===). Returns true
 * only for actual null values, not for empty strings, false, 0, or other falsy
 * values that might evaluate loosely to null in PHP.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsNull extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the operand is strictly null.
     *
     * @param  Context $context Evaluation context providing variable values and state
     *                          for resolving operand values during rule execution
     * @return bool    True if the value is null, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        return $value === null;
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
