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
 * Validates that a value is considered empty according to PHP's empty() semantics.
 *
 * Uses PHP's empty() construct to determine emptiness. Returns true for null,
 * false, 0, "0", "", empty arrays, and unset variables. Follows loose comparison
 * semantics rather than strict type checking.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsEmpty extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the operand is considered empty.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve operand values during rule execution
     * @return bool    True if the value is empty according to PHP's empty() rules, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        return empty($value);
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
