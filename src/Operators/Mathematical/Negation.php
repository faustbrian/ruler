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

/**
 * Negation arithmetic operator.
 *
 * Negates a numeric value by inverting its sign. A positive value becomes
 * negative and vice versa. The operand must resolve to a numeric value.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Negation extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the negated value of the operand.
     *
     * @param  Context $context Evaluation context for resolving the operand
     * @return Value   Value object containing the negated numeric value
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->negate());
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Returns UNARY indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
