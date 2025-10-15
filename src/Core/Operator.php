<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Variables\VariableOperand;
use LogicException;

use function count;
use function throw_if;

/**
 * Base class for all rule operators.
 *
 * Provides the foundational structure for operators in the rule evaluation
 * system, managing operands and enforcing cardinality constraints. Operators
 * can be unary (1 operand), binary (2 operands), or multiple (1+ operands).
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class Operator
{
    /**
     * Collection of operands for this operator.
     *
     * @var array<int, Proposition|VariableOperand>
     */
    protected array $operands = [];

    /**
     * Creates a new operator with the specified operands.
     *
     * Accepts a variable number of operands and adds each one to the
     * operator's operand collection through the addOperand method,
     * which enforces type and cardinality constraints.
     *
     * @param Proposition|VariableOperand ...$operands Variable number of operands
     *                                                 to initialize this operator with
     */
    public function __construct(...$operands)
    {
        foreach ($operands as $operand) {
            $this->addOperand($operand);
        }
    }

    /**
     * Returns the operator's operands with cardinality validation.
     *
     * Validates that the number of operands matches the cardinality
     * requirements defined by getOperandCardinality before returning
     * the operand collection. Throws an exception if the operand count
     * violates the operator's cardinality constraints.
     *
     * @throws LogicException When the operand count does not match the required cardinality
     *
     * @return array<int, Proposition|VariableOperand> Array of operands for this operator
     */
    public function getOperands(): array
    {
        switch ($this->getOperandCardinality()) {
            case OperandCardinality::Unary:
                throw_if(1 !== count($this->operands), LogicException::class, static::class.' takes only 1 operand');

                break;

            case OperandCardinality::Binary:
                throw_if(2 !== count($this->operands), LogicException::class, static::class.' takes 2 operands');

                break;

            case OperandCardinality::Multiple:
                throw_if([] === $this->operands, LogicException::class, static::class.' takes at least 1 operand');

                break;
        }

        return $this->operands;
    }

    /**
     * Adds an operand to this operator.
     *
     * Implementations must define how operands are validated and added
     * to the operator's operand collection, enforcing any type-specific
     * constraints required by the operator.
     *
     * @param Proposition|VariableOperand $operand The operand to add to this operator
     */
    abstract public function addOperand($operand): void;

    /**
     * Returns the operand cardinality for this operator.
     *
     * Implementations must return one of the OperandCardinality enum cases
     * (Unary, Binary, or Multiple) to indicate how many operands
     * this operator requires for proper evaluation.
     *
     * @return OperandCardinality The cardinality enum case
     */
    abstract protected function getOperandCardinality(): OperandCardinality;
}
