<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Operators\Date;

use Carbon\Carbon;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Enums\OperandCardinality;
use Cline\Ruler\Operators\VariableOperator;
use Cline\Ruler\Variables\VariableOperand;
use DateTimeInterface;
use RuntimeException;

use function is_numeric;
use function is_string;

/**
 * Evaluates whether a date/time occurs before another date/time.
 *
 * Compares two date/time values and returns true if the left operand
 * represents a moment in time that occurs before the right operand.
 * Supports Carbon instances, DateTimeInterface objects, and parseable
 * date/time strings or timestamps.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Before extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left date/time operand is before the right date/time operand.
     *
     * @param Context $context Context containing variable values for operand resolution
     *
     * @throws RuntimeException If either operand value cannot be converted to a valid date/time
     *
     * @return bool True if left date/time is before right date/time, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $leftValue = $left->prepareValue($context)->getValue();
        $rightValue = $right->prepareValue($context)->getValue();

        $leftDate = self::convertToCarbon($leftValue);
        $rightDate = self::convertToCarbon($rightValue);

        return $leftDate->isBefore($rightDate);
    }

    /**
     * Returns the operand cardinality for this operator.
     *
     * @return OperandCardinality Binary cardinality (requires exactly two operands)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }

    /**
     * Converts various date/time representations to Carbon instances.
     *
     * Accepts Carbon instances (returned as-is), DateTimeInterface objects,
     * parseable date/time strings, or numeric timestamps.
     *
     * @param mixed $value Value to convert to Carbon instance
     *
     * @throws RuntimeException If value cannot be converted to a valid date/time
     *
     * @return Carbon Carbon instance representing the input value
     */
    private static function convertToCarbon($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) || is_numeric($value)) {
            return Carbon::parse($value);
        }

        throw new RuntimeException('Before: values must be valid date/time representations');
    }
}
