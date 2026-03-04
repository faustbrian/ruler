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
 * Logical XOR (exclusive OR) operator that returns true when exactly one operand evaluates to true.
 *
 * Implements exclusive OR logic where the result is true if and only if exactly one
 * operand evaluates to true within the given context. Returns false when zero operands
 * are true or when multiple operands are true. Uses short-circuit evaluation to optimize
 * performance by returning false immediately once more than one true operand is detected.
 *
 * ```php
 * $xor = new LogicalXor();
 * $xor->addOperand(new Proposition($condition1));
 * $xor->addOperand(new Proposition($condition2));
 * $result = $xor->evaluate($context); // true if exactly one condition is true
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalXor extends LogicalOperator
{
    /**
     * Evaluates whether exactly one operand returns true.
     *
     * Iterates through all proposition operands and evaluates each within the provided
     * context, counting how many evaluate to true. Employs short-circuit evaluation
     * to return false immediately if more than one true operand is detected, avoiding
     * unnecessary evaluations for performance optimization.
     *
     * @param  Context $context Execution context containing variable values and state
     *                          required to evaluate all proposition operands
     * @return bool    True if exactly one operand evaluates to true, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        $true = 0;

        /** @var Proposition $operand */
        foreach ($this->getOperands() as $operand) {
            if (true === $operand->evaluate($context) && ++$true > 1) {
                return false;
            }
        }

        return $true === 1;
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Multiple cardinality indicating this operator accepts two or more operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }
}
