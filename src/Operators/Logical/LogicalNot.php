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
 * Logical NOT operator that returns the negation of its operand's evaluation.
 *
 * Evaluates a single proposition operand and returns its boolean negation.
 * This unary operator inverts the truth value of the operand, returning true
 * when the operand is false, and false when the operand is true. This is the
 * fundamental logical negation operation used in boolean logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNot extends LogicalOperator
{
    /**
     * Evaluates the negation of the operand.
     *
     * Retrieves the single operand and evaluates it within the provided context,
     * then returns the boolean opposite of that evaluation result. This implements
     * standard logical negation where true becomes false and false becomes true.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate the proposition operand
     * @return bool    True if the operand evaluates to false, false if the operand evaluates to true
     */
    public function evaluate(Context $context): bool
    {
        /** @var Proposition $operand */
        [$operand] = $this->getOperands();

        return !$operand->evaluate($context);
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * Logical NOT accepts exactly one operand (unary operation).
     *
     * @return OperandCardinality Unary cardinality constant indicating single operand required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
