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
 * Evaluates whether one date/time occurs chronologically after another.
 *
 * Performs temporal comparison between two date/time values, supporting
 * multiple input formats including Carbon instances, DateTimeInterface objects,
 * ISO 8601 strings, and Unix timestamps. All values are normalized to Carbon
 * instances before comparison to ensure consistent behavior across formats.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class After extends VariableOperator implements Proposition
{
    /**
     * Evaluates whether the left operand date/time is after the right operand date/time.
     *
     * @param Context $context Execution context containing variable values for operand resolution
     *
     * @throws RuntimeException When either operand cannot be converted to a valid date/time
     *
     * @return bool True if left date/time is after right date/time, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        /** @var VariableOperand $left */
        /** @var VariableOperand $right */
        [$left, $right] = $this->getOperands();

        $leftValue = $left->prepareValue($context)->getValue();
        $rightValue = $right->prepareValue($context)->getValue();

        $leftDate = $this->convertToCarbon($leftValue);
        $rightDate = $this->convertToCarbon($rightValue);

        return $leftDate->isAfter($rightDate);
    }

    /**
     * Returns the operand cardinality requirement for this operator.
     *
     * @return OperandCardinality Binary cardinality (exactly two operands required)
     */
    protected function getOperandCardinality(): OperandCardinality
    {
        return OperandCardinality::Binary;
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
    private function convertToCarbon(mixed $value): Carbon
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

        throw new RuntimeException('After: values must be valid date/time representations');
    }
}
