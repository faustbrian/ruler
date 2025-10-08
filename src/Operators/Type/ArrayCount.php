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

/**
 * Array count operator for determining the number of elements in an array or countable object.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ArrayCount extends VariableOperator implements VariableOperand
{
    /**
     * Prepare the count of elements in the operand.
     *
     * Evaluates the single operand and returns the number of elements it contains.
     * The operand must resolve to either an array or an object implementing the
     * Countable interface.
     *
     * @param Context $context The evaluation context containing facts and variables
     *
     * @throws RuntimeException If the operand value is not an array or Countable instance
     *
     * @return Value Value object containing the count of elements in the operand
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        if (!is_array($value) && !$value instanceof Countable) {
            throw new RuntimeException('ArrayCount: value must be an array or countable');
        }

        return new Value(count($value));
    }

    /**
     * Get the required number of operands for this operator.
     *
     * @return OperandCardinality Returns UNARY constant indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
