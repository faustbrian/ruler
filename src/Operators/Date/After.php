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
 * Date/time comparison operator that evaluates whether one date occurs after another.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class After extends VariableOperator implements Proposition
{
    /**
     * Evaluate whether the left date/time is after the right date/time.
     *
     * Both operands are converted to Carbon instances for comparison. Accepts
     * Carbon objects, DateTimeInterface instances, parseable date strings, or
     * numeric timestamps.
     *
     * @param Context $context Context with which to evaluate this proposition
     *
     * @throws RuntimeException If either operand cannot be converted to a valid date/time
     *
     * @return bool True if the left date/time is after the right date/time, false otherwise
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

        return $leftDate->isAfter($rightDate);
    }

    /**
     * Get the required number of operands for this operator.
     *
     * @return OperandCardinality Returns BINARY constant indicating this operator requires exactly two operands
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
    }

    /**
     * Convert various date/time representations to a Carbon instance.
     *
     * Handles multiple input formats including Carbon objects (returned as-is),
     * DateTimeInterface instances (converted via Carbon::instance), and parseable
     * strings or numeric timestamps (parsed via Carbon::parse).
     *
     * @param mixed $value The value to convert to a Carbon instance
     *
     * @throws RuntimeException If the value cannot be converted to a valid date/time
     *
     * @return Carbon Carbon instance representing the input date/time
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

        throw new RuntimeException('After: values must be valid date/time representations');
    }
}
