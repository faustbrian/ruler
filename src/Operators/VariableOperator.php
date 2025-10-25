<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators;

use Cline\Ruler\Core\Operator;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Variables\VariableOperand;
use LogicException;

use function throw_if;

/**
 * Base class for operators that work with variable operands.
 *
 * Extends the base Operator class to provide specialized handling for
 * VariableOperand types, enforcing type safety and cardinality constraints
 * when adding operands to operators in the rule evaluation system.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class VariableOperator extends Operator
{
    /**
     * Adds a variable operand to this operator.
     *
     * Delegates to addVariable method to enforce type-specific validation
     * and constraints for VariableOperand types.
     *
     * @param VariableOperand $operand the variable operand to add to this operator's
     *                                 operand collection for evaluation
     */
    public function addOperand(mixed $operand): void
    {
        $this->addVariable($operand);
    }

    /**
     * Adds a variable operand with cardinality validation.
     *
     * Enforces operand cardinality constraints by preventing unary operators
     * from accepting more than one operand. Throws an exception if a unary
     * operator already has an operand when attempting to add another.
     *
     * @param VariableOperand $operand the variable operand to add to this operator's
     *                                 operand collection for evaluation
     *
     * @throws LogicException when attempting to add a second operand to a unary operator
     */
    public function addVariable(VariableOperand $operand): void
    {
        throw_if(OperandCardinality::Unary === $this->getOperandCardinality()
            && [] !== $this->operands, LogicException::class, static::class.' can only have 1 operand');

        $this->operands[] = $operand;
    }
}
