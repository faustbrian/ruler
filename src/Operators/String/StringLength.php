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

/**
 * String length calculation operator.
 *
 * Computes the character length of a string operand using multibyte-safe
 * character counting. Returns the number of characters in the string,
 * properly handling multibyte character encodings like UTF-8.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringLength extends VariableOperator implements VariableOperand
{
    /**
     * Prepares the string length value for evaluation.
     *
     * Extracts the string value from the operand and calculates its character
     * length using multibyte-safe counting. Ensures UTF-8 and other multibyte
     * encodings are handled correctly, counting characters rather than bytes.
     *
     * @param Context $context Evaluation context containing variable values and state
     *                         used to resolve the operand value during rule evaluation
     *
     * @throws RuntimeException When the operand value is not a string type
     *
     * @return Value Returns a Value object containing the integer character count
     *               of the string operand
     */
    public function prepareValue(Context $context): Value
    {
        /** @var VariableOperand $operand */
        [$operand] = $this->getOperands();

        $value = $operand->prepareValue($context)->getValue();

        if (!is_string($value)) {
            throw new RuntimeException('StringLength: value must be a string');
        }

        return new Value(mb_strlen($value));
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Returns UNARY indicating this operator requires exactly one operand
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Unary;
    }
}
