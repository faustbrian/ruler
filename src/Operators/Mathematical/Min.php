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
 * Minimum value extraction operator for collections.
 *
 * Extracts the minimum value from a collection or set. The operand must resolve
 * to a value containing a set, and this operator returns the lowest value from
 * that set.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Min extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the minimum value from the operand's set.
     *
     * @param  Context $context Evaluation context for resolving the operand
     * @return Value   Value object containing the minimum value from the set
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        return new Value($operand->prepareValue($context)->getSet()->min());
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
