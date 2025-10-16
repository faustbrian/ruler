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
use function throw_unless;

/**
 * Regular expression pattern matching operator.
 *
 * Performs PCRE regex pattern matching to test whether a string value matches
 * a provided pattern. Both operands must resolve to string values or a
 * RuntimeException is thrown, ensuring type safety for regex operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Matches extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the value matches the regex pattern.
     *
     * Resolves both operands from context, validates they are strings, and
     * performs PCRE regex matching to determine if the value matches the
     * provided pattern using PHP's preg_match function.
     *
     * @param Context $context Runtime context containing variable values and state
     *                         for resolving operands during rule evaluation
     *
     * @throws RuntimeException When the left operand value is not a string
     * @throws RuntimeException When the right operand pattern is not a string
     *
     * @return bool True when the left operand value matches the right operand
     *              regex pattern, false when it does not match
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $value = $left->prepareValue($context)->getValue();
        $pattern = $right->prepareValue($context)->getValue();

        throw_unless(is_string($value), RuntimeException::class, 'Matches: value must be a string');

        throw_unless(is_string($pattern), RuntimeException::class, 'Matches: pattern must be a string');

        return (bool) preg_match($pattern, $value);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality requiring exactly two operands
     *                            (string value and PCRE regex pattern)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }
}
