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
use Illuminate\Support\Facades\Date;
use RuntimeException;

use function is_numeric;
use function is_string;

/**
 * Evaluates whether a date falls within an inclusive date range.
 *
 * Performs range validation to determine if a given date falls between
 * two boundary dates (inclusive of boundaries). Supports multiple input
 * formats including Carbon instances, DateTimeInterface objects, ISO 8601
 * strings, and Unix timestamps. All values are normalized to Carbon instances
 * before comparison to ensure consistent behavior across formats.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsBetweenDates extends VariableOperator implements Proposition
{
    /**
     * Creates a new date range operator with three required operands.
     *
     * @param VariableOperand $date  Date value to check for range inclusion
     * @param VariableOperand $start Inclusive start boundary of the date range
     * @param VariableOperand $end   Inclusive end boundary of the date range
     */
    public function __construct(VariableOperand $date, VariableOperand $start, VariableOperand $end)
    {
        parent::__construct($date, $start, $end);
    }

    /**
     * Evaluates whether the date falls within the inclusive range defined by start and end.
     *
     * The range boundaries are inclusive, meaning dates exactly matching the
     * start or end boundaries will return true.
     *
     * @param Context $context Execution context containing variable values for operand resolution
     *
     * @throws RuntimeException When any operand cannot be converted to a valid date/time
     *
     * @return bool True if date is between start and end (inclusive), false otherwise
     */
    public function evaluate(Context $context): bool
    {
        [$date, $start, $end] = $this->getOperands();

        /** @var VariableOperand $date */
        /** @var VariableOperand $start */
        /** @var VariableOperand $end */
        $dateValue = $date->prepareValue($context)->getValue();
        $startValue = $start->prepareValue($context)->getValue();
        $endValue = $end->prepareValue($context)->getValue();

        $dateCarbon = self::convertToCarbon($dateValue);
        $startCarbon = self::convertToCarbon($startValue);
        $endCarbon = self::convertToCarbon($endValue);

        return $dateCarbon->isBetween($startCarbon, $endCarbon, true);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Multiple cardinality (exactly three operands required)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }

    /**
     * Converts various date/time representations to Carbon instances.
     *
     * Handles multiple input formats for flexible date comparison:
     * - Carbon instances are returned as-is for efficiency
     * - DateTimeInterface objects are converted via Carbon::instance()
     * - String values are parsed via Carbon::parse() (supports ISO 8601, relative formats)
     * - Numeric values are treated as Unix timestamps
     *
     * @param mixed $value Value to convert (Carbon, DateTimeInterface, string, or numeric timestamp)
     *
     * @throws RuntimeException When the value type is unsupported or cannot be parsed
     *
     * @return Carbon Carbon instance representing the input date/time
     */
    private static function convertToCarbon(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Date::instance($value);
        }

        if (is_string($value) || is_numeric($value)) {
            return Date::parse($value);
        }

        throw new RuntimeException('IsBetweenDates: values must be valid date/time representations');
    }
}
