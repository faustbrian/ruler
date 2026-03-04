<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Logical;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;

/**
 * Logical NOT operator that returns the boolean negation of its operand.
 *
 * Implements boolean negation (¬) over a single proposition. This unary operator
 * inverts the truth value of its operand, returning true when the operand is false
 * and false when the operand is true. This is the fundamental logical negation
 * operation used in boolean algebra and follows standard NOT truth table logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNot extends LogicalOperator
{
    /**
     * Evaluates the boolean negation of the single proposition operand.
     *
     * @param  Context $context Execution context containing variable values for operand evaluation
     * @return bool    True if operand is false, false if operand is true
     */
    public function evaluate(Context $context): bool
    {
        /** @var Proposition $operand */
        [$operand] = $this->getOperands();

        return !$operand->evaluate($context);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Unary cardinality (exactly one operand required)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
