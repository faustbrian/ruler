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
 * Evaluates whether a string value does not match a regular expression pattern.
 *
 * This operator performs regex pattern matching and returns true when the value
 * does NOT match the provided pattern. Both operands must resolve to string values
 * or a RuntimeException is thrown.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class DoesNotMatch extends VariableOperator implements Proposition
{
    /**
     * Evaluates the regex non-match comparison between left and right operands.
     *
     * @param Context $context Context containing variables and values for operand resolution
     *
     * @throws RuntimeException When the left operand value is not a string
     * @throws RuntimeException When the right operand pattern is not a string
     *
     * @return bool True if the left operand value does not match the right operand pattern
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $value = $left->prepareValue($context)->getValue();
        $pattern = $right->prepareValue($context)->getValue();

        if (!is_string($value)) {
            throw new RuntimeException('DoesNotMatch: value must be a string');
        }

        if (!is_string($pattern)) {
            throw new RuntimeException('DoesNotMatch: pattern must be a string');
        }

        return !(bool) preg_match($pattern, $value);
    }

    /**
     * Returns the number of operands required by this operator.
     *
     * @return OperandCardinality Binary operator requiring exactly two operands (value and pattern)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
