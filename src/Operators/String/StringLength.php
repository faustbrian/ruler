<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\String;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\VariableOperand;
use RuntimeException;

use function is_string;
use function mb_strlen;
use function throw_unless;

/**
 * Calculates the character length of a string using multibyte-safe counting.
 *
 * Computes the number of characters in a string operand using mb_strlen() for
 * proper Unicode and multibyte encoding support. Counts characters rather than
 * bytes, ensuring accurate length calculation for UTF-8 strings.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringLength extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the string length value for evaluation.
     *
     * @param Context $context Evaluation context containing variable values and state
     *                         used to resolve the operand value during rule execution
     *
     * @throws RuntimeException When the operand value is not a string
     *
     * @return Value Value object containing the integer character count of the string
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        throw_unless(is_string($value), RuntimeException::class, 'StringLength: value must be a string');

        return new Value(mb_strlen($value));
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
