<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Values;

use Countable;
use RuntimeException;
use Stringable;

use function array_any;
use function array_diff;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_sum;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_countable;
use function is_object;
use function max;
use function method_exists;
use function min;
use function throw_unless;

/**
 * Immutable set value for collection operations and comparisons.
 *
 * Set provides set-theoretic operations (union, intersection, complement)
 * and numeric aggregations (min, max). Values are automatically normalized
 * to arrays, deduplicated, and nested arrays are recursively wrapped in
 * Set instances.
 *
 * Sets are immutable once constructed, ensuring consistent evaluation results
 * during rule execution. The Countable interface enables direct count() usage,
 * and Stringable provides string representation for debugging and logging.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class Set implements Countable, Stringable
{
    /**
     * The normalized array of set elements.
     *
     * @var array<int|string, mixed>
     */
    private array $value;

    /**
     * Create a new immutable Set from the given value.
     *
     * The constructor normalizes input values:
     * - Null values become empty arrays
     * - Non-array values are wrapped in single-element arrays
     * - Array values are recursively wrapped in Set instances
     * - Objects without __toString are wrapped in Value instances
     * - All values are deduplicated using array_unique
     *
     * @param mixed $set The value to convert to a Set. Can be an array, scalar,
     *                   object, or null. All values are normalized to array format.
     */
    public function __construct(mixed $set)
    {
        if (!is_array($set)) {
            $set = null === $set ? [] : [$set];
        }

        foreach ($set as &$value) {
            if (is_array($value)) {
                $value = new self($value);
            } elseif (is_object($value)) {
                if (!method_exists($value, '__toString')) {
                    $value = new Value($value);
                }
            }
        }

        $set = array_unique($set);

        foreach ($set as &$value) {
            if (!$value instanceof Value) {
                continue;
            }

            $value = $value->getValue();
        }

        $this->value = $set;
    }

    /**
     * Convert the Set to its string representation.
     *
     * @return string concatenation of all set elements cast to strings
     */
    public function __toString(): string
    {
        $returnValue = '';

        foreach ($this->value as $value) {
            /** @phpstan-ignore cast.string */
            $returnValue .= (string) $value;
        }

        return $returnValue;
    }

    /**
     * Retrieve the wrapped array value.
     *
     * @return array<int|string, mixed> the set elements as an array
     */
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * Get a Set instance (returns self since already a Set).
     *
     * This method provides compatibility with Value's getSet() method,
     * enabling polymorphic set operations.
     *
     * @return self this Set instance
     */
    public function getSet(): self
    {
        return $this;
    }

    /**
     * Check if the set contains a specific value or nested set.
     *
     * For array values, checks if any element in this set is a Set with
     * exactly matching contents. For scalar values, performs strict
     * equality check against all set members.
     *
     * @param  Value $value the value to search for within this set
     * @return bool  true if the value is found in the set, false otherwise
     */
    public function setContains(Value $value): bool
    {
        if (is_array($value->getValue())) {
            return array_any($this->value, fn ($val): bool => $val instanceof self && $val->getValue() === $value->getSet()->getValue());
        }

        return in_array($value->getValue(), $this->value, true);
    }

    /**
     * Create a new Set containing the union of this set with one or more other sets.
     *
     * The union includes all unique elements from this set and all provided sets,
     * with duplicates automatically removed.
     *
     * @param  Value ...$sets one or more Value instances to union with this set.
     *                        Non-Set values are automatically converted to Sets.
     * @return self  a new Set instance containing the union of all sets
     */
    public function union(Value ...$sets): self
    {
        $union = $this->value;

        /** @var Value $arg */
        foreach ($sets as $arg) {
            /** @var array<int|string, mixed> $convertedArg */
            $convertedArg = $arg->getSet()->getValue();

            $union = array_merge($union, array_diff($convertedArg, $union));
        }

        return new self($union);
    }

    /**
     * Create a new Set containing the intersection of this set with one or more other sets.
     *
     * The intersection includes only elements that exist in this set AND all
     * provided sets.
     *
     * @param  Value ...$sets one or more Value instances to intersect with this set.
     *                        Non-Set values are automatically converted to Sets.
     * @return self  a new Set instance containing only common elements
     */
    public function intersect(Value ...$sets): self
    {
        $intersect = $this->value;

        /** @var Value $arg */
        foreach ($sets as $arg) {
            /** @var array<int|string, mixed> $convertedArg */
            $convertedArg = $arg->getSet()->getValue();

            // array_values reindexes to ensure sequential numeric keys
            $intersect = array_values(array_intersect($intersect, $convertedArg));
        }

        return new self($intersect);
    }

    /**
     * Create a new Set containing the complement of this set relative to other sets.
     *
     * The complement includes elements in this set that are NOT in any of the
     * provided sets (set difference).
     *
     * @param  Value ...$sets one or more Value instances to exclude from this set.
     *                        Non-Set values are automatically converted to Sets.
     * @return self  a new Set instance containing only elements not in other sets
     */
    public function complement(Value ...$sets): self
    {
        /** @var array<int|string, mixed> $complement */
        $complement = $this->value;

        /** @var Value $arg */
        foreach ($sets as $arg) {
            /** @var array<int|string, mixed> $convertedArg */
            $convertedArg = $arg->getSet()->getValue();
            // array_values reindexes to ensure sequential numeric keys
            $complement = array_values(array_diff($complement, $convertedArg));
        }

        return new self($complement);
    }

    /**
     * Create a new Set containing the symmetric difference between this set and another.
     *
     * The symmetric difference includes elements that exist in either set but not
     * in both (exclusive or). Equivalent to (A - B) ∪ (B - A).
     *
     * @param  Value $set the set to compare against for symmetric difference
     * @return self  a new Set instance containing elements in either set but not both
     */
    public function symmetricDifference(Value $set): self
    {
        $returnValue = new self([]);

        return $returnValue->union(
            $this->complement($set)->asValue(),
            $set->getSet()->complement($this->asValue())->asValue(),
        );
    }

    /**
     * Find the minimum numeric value in this set.
     *
     * @throws RuntimeException when the set contains non-numeric values
     *
     * @return mixed the smallest numeric value in the set, or null for empty sets
     */
    public function min(): mixed
    {
        throw_unless($this->isValidNumericSet(), RuntimeException::class, 'min: all values must be numeric');

        if ($this->value === []) {
            return null;
        }

        /** @var non-empty-array<int|string, mixed> $value */
        $value = $this->value;

        return min($value);
    }

    /**
     * Find the maximum numeric value in this set.
     *
     * @throws RuntimeException when the set contains non-numeric values
     *
     * @return mixed the largest numeric value in the set, or null for empty sets
     */
    public function max(): mixed
    {
        throw_unless($this->isValidNumericSet(), RuntimeException::class, 'max: all values must be numeric');

        if ($this->value === []) {
            return null;
        }

        /** @var non-empty-array<int|string, mixed> $value */
        $value = $this->value;

        return max($value);
    }

    /**
     * Check if this set contains all elements from another set as a subset.
     *
     * @param  self $set the potential subset to check for within this set
     * @return bool true if the provided set is a subset of this set, false otherwise
     */
    public function containsSubset(self $set): bool
    {
        if (count($set->getValue()) > count($this->value)) {
            return false;
        }

        /** @var array<int|string, mixed> $setValue */
        $setValue = $set->getValue();

        /** @var array<int|string, mixed> $thisValue */
        $thisValue = $this->value;

        return array_intersect($setValue, $thisValue) === $setValue;
    }

    /**
     * Get the number of elements in this set.
     *
     * Implements the Countable interface, enabling direct count() usage.
     *
     * @return int the number of elements in the set
     */
    public function count(): int
    {
        return count($this->value);
    }

    /**
     * Convert this Set to a Value instance.
     *
     * Used when a Value is needed for operations that expect Value types.
     *
     * @return Value a new Value wrapping this Set's array value
     */
    public function asValue(): Value
    {
        return new Value($this->value);
    }

    /**
     * Perform equality comparison using strict equality on the underlying arrays.
     *
     * @param  self $set the set to compare against
     * @return bool true if sets contain identical elements, false otherwise
     */
    public function equalTo(self $set): bool
    {
        return $this->value === $set->getValue();
    }

    /**
     * Validate that all set members are numeric values.
     *
     * @return bool true if all values are numeric, false otherwise
     */
    private function isValidNumericSet(): bool
    {
        /** @var array<int|string, mixed> $value */
        $value = $this->value;

        /** @phpstan-ignore function.alreadyNarrowedType */
        return (is_countable($value) ? count($value) : 0) === array_sum(array_map(is_numeric(...), $value));
    }
}
