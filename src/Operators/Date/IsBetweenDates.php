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
 * Validates that a date falls within a specified date range.
 *
 * Checks whether a given date is between two boundary dates (inclusive). Accepts
 * various date formats including Carbon instances, DateTimeInterface objects,
 * date strings, and Unix timestamps. All values are converted to Carbon instances
 * for consistent comparison logic.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class IsBetweenDates extends VariableOperator implements Proposition
{
    /**
     * Creates a new date range operator with date, start, and end operands.
     *
     * @param VariableOperand $date  The date value to check against the range
     * @param VariableOperand $start The inclusive start boundary of the date range
     * @param VariableOperand $end   The inclusive end boundary of the date range
     */
    public function __construct(VariableOperand $date, VariableOperand $start, VariableOperand $end)
    {
        parent::__construct($date, $start, $end);
    }

    /**
     * Evaluates whether the date operand falls within the start and end range.
     *
     * All operand values are converted to Carbon instances before comparison.
     * The range boundaries are inclusive, meaning dates exactly matching the
     * start or end dates will return true.
     *
     * @param Context $context Evaluation context providing variable values and state
     *                         for resolving operand values during rule execution
     *
     * @throws RuntimeException When any operand cannot be converted to a valid date/time
     *
     * @return bool True if the date is between start and end (inclusive), false otherwise
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
     * Returns the required number of operands for this operator.
     *
     * @return OperandCardinality Multiple cardinality constant indicating three operands required
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Multiple;
    }

    /**
     * Converts various date/time representations to Carbon instances.
     *
     * Handles multiple input formats for flexible date comparison, including
     * Carbon objects (returned as-is), DateTimeInterface implementations
     * (converted via Carbon::instance), and string or numeric values parsed
     * through Carbon::parse which supports ISO 8601, relative formats, and
     * Unix timestamps.
     *
     * @param mixed $value The date/time value to convert (Carbon, DateTimeInterface, string, or numeric)
     *
     * @throws RuntimeException When the value cannot be converted to a valid date/time
     *
     * @return Carbon The Carbon instance representing the provided date/time
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

        throw new RuntimeException('IsBetweenDates: values must be valid date/time representations');
    }
}
