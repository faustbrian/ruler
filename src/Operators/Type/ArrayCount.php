<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Type;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;
use Countable;
use RuntimeException;

use function count;
use function is_array;
use function throw_if;

/**
 * Counts the number of elements in an array or Countable object.
 *
 * Evaluates the operand and returns the count of elements it contains using
 * PHP's count() function. Accepts both arrays and objects implementing the
 * Countable interface.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ArrayCount extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the element count value for evaluation.
     *
     * @param Context $context Evaluation context containing variable values and state
     *                         used to resolve the operand value during rule execution
     *
     * @throws RuntimeException When the operand value is neither an array nor a Countable instance
     *
     * @return Value Value object containing the integer count of elements
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        throw_if(!is_array($value) && !$value instanceof Countable, RuntimeException::class, 'ArrayCount: value must be an array or countable');

        return new Value(count($value));
    }

    /**
     * Returns the required number of operands for this operator.
     *
     * @return OperandCardinality Unary cardinality constant indicating one operand required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
