<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Set;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;

/**
 * Evaluates whether a set contains all elements of another set.
 *
 * Performs a subset containment check where the left operand (superset)
 * must contain all elements present in the right operand (subset). Returns
 * true when the right set is a subset of or equal to the left set.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ContainsSubset extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left set contains all elements of the right set.
     *
     * @param  Context $context Context containing variable values for operand resolution
     * @return bool    True if left set contains all elements of right set, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        return $left->prepareValue($context)->getSet()
            ->containsSubset($right->prepareValue($context)->getSet());
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Binary cardinality (requires exactly two set operands)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
