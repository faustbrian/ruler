<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\String;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Evaluates whether a string starts with a prefix (case-insensitive).
 *
 * Performs case-insensitive prefix matching to determine if the left
 * operand string begins with the right operand substring. Character
 * case is ignored during comparison.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StartsWithInsensitive extends VariableOperator implements Proposition
{
    /**
     * Evaluates the case-insensitive starts-with comparison.
     *
     * @param  Context $context Runtime context containing variable values and state
     *                          for evaluating this proposition
     * @return bool    True when the left operand starts with the right operand
     *                 (case-insensitive), false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->startsWith($right->prepareValue($context), true);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality constant requiring exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
