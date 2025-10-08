<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\String;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;
use RuntimeException;

use function is_string;
use function preg_match;

/**
 * Regular expression pattern matching operator.
 *
 * Performs pattern matching using PCRE regex patterns. Both operands must resolve
 * to string values, with the left operand being the value to test and the right
 * operand being the regex pattern to match against.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Matches extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand matches the regex pattern in the right operand.
     *
     * @param Context $context Context with which to evaluate this Proposition
     *
     * @throws RuntimeException If the left operand value is not a string
     * @throws RuntimeException If the right operand pattern is not a string
     *
     * @return bool Returns true if the value matches the pattern, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $value = $left->prepareValue($context)->getValue();
        $pattern = $right->prepareValue($context)->getValue();

        if (!is_string($value)) {
            throw new RuntimeException('Matches: value must be a string');
        }

        if (!is_string($pattern)) {
            throw new RuntimeException('Matches: pattern must be a string');
        }

        return (bool) preg_match($pattern, $value);
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Returns BINARY indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
