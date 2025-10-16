<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotIn;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Variables\Variable;
use LogicException;
use ReflectionClass;

use function array_map;
use function array_values;
use function count;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function method_exists;
use function sprintf;
use function throw_if;

/**
 * Serializes Rule objects back to Natural Language DSL expression strings.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to human-readable natural language syntax. Supports all natural language
 * operators including comparison, logical, range, list membership, and string
 * operations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $serializer = new NaturalLanguageSerializer();
 * $parser = new NaturalLanguageParser();
 *
 * $rule = $parser->parse('age is greater than or equal to 18 and country equals US');
 * $expression = $serializer->serialize($rule);
 * // Returns: 'age is greater than or equal to 18 and country equals US'
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see NaturalLanguageParser For parsing DSL strings into Rules
 * @see NaturalLanguageValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class NaturalLanguageSerializer
{
    /**
     * Serialize a Rule to a Natural Language DSL expression string.
     *
     * Walks the Rule's Proposition tree and reconstructs the original
     * natural language syntax in human-readable format.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return string The Natural Language DSL expression
     */
    public function serialize(Rule $rule): string
    {
        $reflection = new ReflectionClass($rule);
        $conditionProperty = $reflection->getProperty('condition');

        /** @var Proposition $condition */
        $condition = $conditionProperty->getValue($rule);

        return $this->serializeProposition($condition);
    }

    /**
     * Serialize a Proposition to DSL syntax.
     *
     * @param  Proposition $proposition The proposition to serialize
     * @return string      The serialized expression
     */
    private function serializeProposition(Proposition $proposition): string
    {
        return match (true) {
            // Comparison operators
            $proposition instanceof EqualTo => self::serializeComparison($proposition, 'equals'),
            $proposition instanceof NotEqualTo => self::serializeComparison($proposition, 'is not'),
            $proposition instanceof GreaterThan => self::serializeComparison($proposition, 'is greater than'),
            $proposition instanceof GreaterThanOrEqualTo => self::serializeComparison($proposition, 'is greater than or equal to'),
            $proposition instanceof LessThan => self::serializeComparison($proposition, 'is less than'),
            $proposition instanceof LessThanOrEqualTo => self::serializeComparison($proposition, 'is less than or equal to'),
            $proposition instanceof Between => self::serializeBetween($proposition),
            $proposition instanceof In => self::serializeIn($proposition),
            $proposition instanceof NotIn => self::serializeNotIn($proposition),
            // Logical operators
            $proposition instanceof LogicalAnd => $this->serializeLogical($proposition, 'and'),
            $proposition instanceof LogicalOr => $this->serializeLogical($proposition, 'or'),
            // String operators
            $proposition instanceof StringContains => self::serializeStringOperation($proposition, 'contains'),
            $proposition instanceof StartsWith => self::serializeStringOperation($proposition, 'starts with'),
            $proposition instanceof EndsWith => self::serializeStringOperation($proposition, 'ends with'),
            default => throw new LogicException(sprintf('Unsupported operator for natural language serialization: %s', $proposition::class)),
        };
    }

    /**
     * Serialize a comparison operator.
     *
     * @param  Proposition $proposition The proposition containing two operands
     * @param  string      $operator    The natural language operator phrase
     * @return string      The serialized comparison expression
     */
    private static function serializeComparison(Proposition $proposition, string $operator): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, sprintf('Comparison operator %s requires exactly 2 operands', $operator));

        $field = self::serializeField($operands[0]);
        $value = self::serializeValue($operands[1]);

        return sprintf('%s %s %s', $field, $operator, $value);
    }

    /**
     * Serialize a between range check.
     *
     * @param  Between $between The between proposition
     * @return string  The serialized between expression
     */
    private static function serializeBetween(Between $between): string
    {
        $operands = self::getOperands($between);

        throw_if(count($operands) !== 3, LogicException::class, 'Between operator requires exactly 3 operands');

        $field = self::serializeField($operands[0]);
        $min = self::serializeValue($operands[1]);
        $max = self::serializeValue($operands[2]);

        return sprintf('%s is between %s and %s', $field, $min, $max);
    }

    /**
     * Serialize an In list membership check.
     *
     * @param  In     $in The in proposition
     * @return string The serialized in expression
     */
    private static function serializeIn(In $in): string
    {
        $operands = self::getOperands($in);

        throw_if(count($operands) !== 2, LogicException::class, 'In operator requires exactly 2 operands');

        $field = self::serializeField($operands[0]);
        $values = self::getListValues($operands[1]);

        if (count($values) === 2) {
            return sprintf('%s is either %s or %s', $field, self::serializeValue($values[0]), self::serializeValue($values[1]));
        }

        $serializedValues = array_map(
            fn ($value): string => self::serializeValue($value),
            $values,
        );

        return sprintf('%s is one of %s', $field, implode(', ', $serializedValues));
    }

    /**
     * Serialize a NotIn list membership check.
     *
     * @param  NotIn  $notIn The not in proposition
     * @return string The serialized not in expression
     */
    private static function serializeNotIn(NotIn $notIn): string
    {
        $operands = self::getOperands($notIn);

        throw_if(count($operands) !== 2, LogicException::class, 'NotIn operator requires exactly 2 operands');

        $field = self::serializeField($operands[0]);
        $values = self::getListValues($operands[1]);

        $serializedValues = array_map(
            fn ($value): string => self::serializeValue($value),
            $values,
        );

        return sprintf('%s is not one of %s', $field, implode(', ', $serializedValues));
    }

    /**
     * Serialize a string operation.
     *
     * @param  Proposition $proposition The proposition containing two operands
     * @param  string      $operation   The natural language operation phrase
     * @return string      The serialized string operation expression
     */
    private static function serializeStringOperation(Proposition $proposition, string $operation): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, sprintf('String operation %s requires exactly 2 operands', $operation));

        $field = self::serializeField($operands[0]);
        $value = self::serializeValue($operands[1]);

        return sprintf('%s %s %s', $field, $operation, $value);
    }

    /**
     * Serialize a logical operator (and, or).
     *
     * @param  Proposition $proposition The logical proposition
     * @param  string      $operator    The logical operator keyword
     * @return string      The serialized logical expression
     */
    private function serializeLogical(Proposition $proposition, string $operator): string
    {
        $operands = self::getOperands($proposition);

        $serialized = array_map(
            fn ($operand): string => $operand instanceof Proposition
                ? $this->wrapIfNeeded($operand, $operator)
                : self::serializeValue($operand),
            $operands,
        );

        return implode(' '.$operator.' ', $serialized);
    }

    /**
     * Serialize a field reference (Variable).
     *
     * @param  mixed  $operand The operand to serialize as a field
     * @return string The serialized field name
     */
    private static function serializeField(mixed $operand): string
    {
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            $name = $operand->getName();

            if ($name !== null) {
                return $name;
            }
        }

        throw new LogicException('Expected variable with name for field reference');
    }

    /**
     * Serialize a value (string, number, boolean, null).
     *
     * @param  mixed  $operand The operand to serialize as a value
     * @return string The serialized value
     */
    private static function serializeValue(mixed $operand): string
    {
        // Handle Variables with values
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            $value = $operand->getValue();

            return self::formatValue($value);
        }

        return self::formatValue($operand);
    }

    /**
     * Format a raw value as natural language syntax.
     *
     * @param  mixed  $value The value to format
     * @return string The formatted value
     */
    private static function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return 'null';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        throw new LogicException(sprintf('Cannot format value of type: %s', gettype($value)));
    }

    /**
     * Get list values from a Variable operand.
     *
     * @param  mixed             $operand The operand containing list values
     * @return array<int, mixed> The list values
     */
    private static function getListValues(mixed $operand): array
    {
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            $value = $operand->getValue();

            if (is_array($value)) {
                return array_values($value);
            }
        }

        throw new LogicException('Expected variable with array value for list membership');
    }

    /**
     * Wrap expression in parentheses if needed based on operator precedence.
     *
     * @param  Proposition $proposition The proposition to potentially wrap
     * @param  string      $parentOp    The parent operator
     * @return string      The wrapped or unwrapped expression
     */
    private function wrapIfNeeded(Proposition $proposition, string $parentOp): string
    {
        $needsWrap = match (true) {
            // Wrap OR inside AND: (a or b) and c
            $proposition instanceof LogicalOr && $parentOp === 'and' => true,
            default => false,
        };

        $serialized = $this->serializeProposition($proposition);

        return $needsWrap ? sprintf('(%s)', $serialized) : $serialized;
    }

    /**
     * Get operands from an operator using reflection.
     *
     * @param  object            $operator The operator object
     * @return array<int, mixed> The operands
     */
    private static function getOperands(object $operator): array
    {
        $reflection = new ReflectionClass($operator);

        // Try to get operands property
        if ($reflection->hasProperty('operands')) {
            $operandsProperty = $reflection->getProperty('operands');
            $value = $operandsProperty->getValue($operator);

            throw_if(!is_array($value), LogicException::class, 'Operands property must be an array');

            return array_values($value);
        }

        // Fallback: call getOperands() method if it exists
        if (method_exists($operator, 'getOperands')) {
            $result = $operator->getOperands();

            throw_if(!is_array($result), LogicException::class, 'getOperands() must return an array');

            return array_values($result);
        }

        return [];
    }
}
