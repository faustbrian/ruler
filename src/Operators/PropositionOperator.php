<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators;

use Cline\Ruler\Core\Operator;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use LogicException;

use function throw_if;

/**
 * Base operator for proposition-based logical operations.
 *
 * Extends the base operator to specifically handle Proposition operands
 * with cardinality validation. Ensures operators only accept the correct
 * number of proposition operands based on their defined cardinality.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class PropositionOperator extends Operator
{
    /**
     * Adds a proposition operand to this operator.
     *
     * @param Proposition $operand Proposition operand to add to the operator's
     *                             operand collection
     */
    public function addOperand($operand): void
    {
        $this->addProposition($operand);
    }

    /**
     * Adds a proposition operand with cardinality validation.
     *
     * Validates that unary operators do not receive more than one operand
     * before adding the proposition to the operand collection.
     *
     * @param Proposition $operand Proposition operand to add with validation
     *
     * @throws LogicException When attempting to add more than one operand
     *                        to a unary operator
     */
    public function addProposition(Proposition $operand): void
    {
        throw_if(OperandCardinality::Unary === $this->getOperandCardinality()
            && [] !== $this->operands, LogicException::class, static::class.' can only have 1 operand');

        $this->operands[] = $operand;
    }
}
