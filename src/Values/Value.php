<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Values;

use RuntimeException;
use Stringable;

use function ceil;
use function floor;
use function is_numeric;
use function is_object;
use function mb_stripos;
use function mb_strlen;
use function serialize;
use function spl_object_hash;
use function str_contains;
use function substr_compare;

/**
 * Immutable terminal value for comparisons and arithmetic operations.
 *
 * Value represents the final, resolved value used in rule evaluation. Variables
 * and comparison operators are resolved to Value instances by applying the current
 * Context and default variable values. Values are immutable once constructed,
 * ensuring consistent evaluation results.
 *
 * The class provides comparison methods (equality, relational), string operations
 * (contains, starts/ends with), and arithmetic operations (add, subtract, multiply).
 * All operations return raw values rather than Value instances for operator chaining.
 *
 * @author Brian Faust <brian@cline.sh>
 */
class Value implements Stringable
{
    /**
     * The immutable value wrapped by this instance.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new immutable Value instance.
     *
     * Value objects are immutable containers used by Variables to compare default
     * values or facts from the current evaluation Context. Once constructed, the
     * wrapped value cannot be changed.
     *
     * @param mixed $value The immutable value to wrap. Can be any type including
     *                     scalars, arrays, objects, or null.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Convert the Value to its string representation.
     *
     * For objects, returns the object hash. For all other types, returns
     * the serialized representation of the value.
     *
     * @return string the string representation of this value
     */
    public function __toString(): string
    {
        if (is_object($this->value)) {
            return spl_object_hash($this->value);
        } else {
            return serialize($this->value);
        }
    }

    /**
     * Retrieve the wrapped value.
     *
     * @return mixed the immutable value wrapped by this instance
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get a Set instance containing this value.
     *
     * @return Set a new Set wrapping this value, normalized according to Set rules
     */
    public function getSet(): Set
    {
        return new Set($this->value);
    }

    /**
     * Perform equality comparison using strict equality (===).
     *
     * @param  self $value the value to compare against
     * @return bool true if values are strictly equal, false otherwise
     */
    public function equalTo(self $value): bool
    {
        return $this->value === $value->getValue();
    }

    /**
     * Perform identity comparison using strict equality (===).
     *
     * Functionally identical to equalTo() - both use strict equality.
     *
     * @param  self $value the value to compare against
     * @return bool true if values are strictly equal, false otherwise
     */
    public function sameAs(self $value): bool
    {
        return $this->value === $value->getValue();
    }

    /**
     * Check if this string value contains another string (case-sensitive).
     *
     * @param  self $value the substring to search for within this value
     * @return bool true if this value contains the substring, false otherwise
     */
    public function stringContains(self $value): bool
    {
        /** @phpstan-ignore cast.string, cast.string */
        return str_contains((string) $this->value, (string) $value->getValue());
    }

    /**
     * Check if this string value contains another string (case-insensitive).
     *
     * @param  self $value the substring to search for (case-insensitive) within this value
     * @return bool true if this value contains the substring, false otherwise
     */
    public function stringContainsInsensitive(self $value): bool
    {
        /** @phpstan-ignore cast.string, cast.string */
        return mb_stripos((string) $this->value, (string) $value->getValue()) !== false;
    }

    /**
     * Perform greater than comparison.
     *
     * @param  self $value the value to compare against
     * @return bool true if this value is greater than the compared value, false otherwise
     */
    public function greaterThan(self $value): bool
    {
        return $this->value > $value->getValue();
    }

    /**
     * Perform less than comparison.
     *
     * @param  self $value the value to compare against
     * @return bool true if this value is less than the compared value, false otherwise
     */
    public function lessThan(self $value): bool
    {
        return $this->value < $value->getValue();
    }

    /**
     * Add another value to this value (arithmetic addition).
     *
     * @param self $value the value to add to this value
     *
     * @throws RuntimeException when either value is not numeric
     *
     * @return float|int the sum of the two values
     */
    public function add(self $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return $this->value + $value->getValue();
    }

    /**
     * Divide this value by another value (arithmetic division).
     *
     * @param self $value the divisor value
     *
     * @throws RuntimeException when either value is not numeric or when dividing by zero
     *
     * @return float|int the quotient of the division
     */
    public function divide(self $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        if (0 === $value->getValue()) {
            throw new RuntimeException('Division by zero');
        }

        return $this->value / $value->getValue();
    }

    /**
     * Calculate the modulo of this value by another value.
     *
     * @param self $value the divisor for the modulo operation
     *
     * @throws RuntimeException when either value is not numeric or when dividing by zero
     *
     * @return int the remainder of the division
     */
    public function modulo(self $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        if (0 === $value->getValue()) {
            throw new RuntimeException('Division by zero');
        }

        return $this->value % $value->getValue();
    }

    /**
     * Multiply this value by another value (arithmetic multiplication).
     *
     * @param self $value the value to multiply with this value
     *
     * @throws RuntimeException when either value is not numeric
     *
     * @return float|int the product of the multiplication
     */
    public function multiply(self $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return $this->value * $value->getValue();
    }

    /**
     * Subtract another value from this value (arithmetic subtraction).
     *
     * @param self $value the value to subtract from this value
     *
     * @throws RuntimeException when either value is not numeric
     *
     * @return float|int the difference of the subtraction
     */
    public function subtract(self $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return $this->value - $value->getValue();
    }

    /**
     * Negate this numeric value (arithmetic negation).
     *
     * @throws RuntimeException when the value is not numeric
     *
     * @return float|int the negated value
     */
    public function negate()
    {
        if (!is_numeric($this->value)) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return -$this->value;
    }

    /**
     * Round this numeric value up to the nearest integer (ceiling).
     *
     * @throws RuntimeException when the value is not numeric
     *
     * @return int the rounded up integer value
     */
    public function ceil()
    {
        if (!is_numeric($this->value)) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return (int) ceil((float) $this->value);
    }

    /**
     * Round this numeric value down to the nearest integer (floor).
     *
     * @throws RuntimeException when the value is not numeric
     *
     * @return int the rounded down integer value
     */
    public function floor()
    {
        if (!is_numeric($this->value)) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return (int) floor((float) $this->value);
    }

    /**
     * Raise this value to the power of another value (exponentiation).
     *
     * @param self $value the exponent value
     *
     * @throws RuntimeException when either value is not numeric
     *
     * @return float|int the result of the exponentiation
     */
    public function exponentiate(self $value)
    {
        if (!is_numeric($this->value) || !is_numeric($value->getValue())) {
            throw new RuntimeException('Arithmetic: values must be numeric');
        }

        return $this->value ** $value->getValue();
    }

    /**
     * Check if this string value starts with another string.
     *
     * @param  self $value       the prefix to check for at the start of this value
     * @param  bool $insensitive whether to perform case-insensitive comparison
     * @return bool true if this value starts with the prefix, false otherwise
     */
    public function startsWith(self $value, bool $insensitive = false): bool
    {
        $value = $value->getValue();
        /** @phpstan-ignore cast.string */
        $valueLength = mb_strlen((string) $value);

        /** @phpstan-ignore cast.string */
        if (!empty($this->value) && !empty($value) && mb_strlen((string) $this->value) >= $valueLength) {
            /** @phpstan-ignore cast.string, cast.string */
            return substr_compare((string) $this->value, (string) $value, 0, $valueLength, $insensitive) === 0;
        }

        return false;
    }

    /**
     * Check if this string value ends with another string.
     *
     * @param  self $value       the suffix to check for at the end of this value
     * @param  bool $insensitive whether to perform case-insensitive comparison
     * @return bool true if this value ends with the suffix, false otherwise
     */
    public function endsWith(self $value, bool $insensitive = false): bool
    {
        $value = $value->getValue();
        /** @phpstan-ignore cast.string */
        $valueLength = mb_strlen((string) $value);

        /** @phpstan-ignore cast.string */
        if (!empty($this->value) && !empty($value) && mb_strlen((string) $this->value) >= $valueLength) {
            /** @phpstan-ignore cast.string, cast.string */
            return substr_compare((string) $this->value, (string) $value, -$valueLength, $valueLength, $insensitive) === 0;
        }

        return false;
    }
}
