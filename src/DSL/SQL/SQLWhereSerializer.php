<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

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
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\String\DoesNotMatch;
use Cline\Ruler\Operators\String\Matches;
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
use function mb_strlen;
use function method_exists;
use function preg_match;
use function sprintf;
use function str_replace;
use function throw_if;
use function throw_unless;

/**
 * Serializes Rule objects back to SQL WHERE clause expression strings.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to human-readable SQL WHERE clause syntax. Supports all SQL operators
 * including comparison, logical, set operations, and null checks.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $serializer = new SQLWhereSerializer();
 * $parser = new SQLWhereParser();
 *
 * $rule = $parser->parse("age >= 18 AND country = 'US'");
 * $expression = $serializer->serialize($rule);
 * // Returns: "age >= 18 AND country = 'US'"
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see SQLWhereParser For parsing SQL WHERE strings into Rules
 * @see SQLWhereValidator For validating SQL WHERE strings
 *
 * @psalm-immutable
 */
final readonly class SQLWhereSerializer
{
    /**
     * Serialize a Rule to a SQL WHERE clause expression string.
     *
     * Walks the Rule's Proposition tree and reconstructs the original
     * SQL WHERE clause syntax. Supports operator precedence and automatic
     * parenthesization for complex expressions.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return string The SQL WHERE clause expression
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
     * Serialize a Proposition to SQL syntax.
     *
     * @param  Proposition $proposition The proposition to serialize
     * @return string      The serialized expression
     */
    private function serializeProposition(Proposition $proposition): string
    {
        return match (true) {
            // Comparison operators
            $proposition instanceof EqualTo => $this->serializeBinary($proposition, '='),
            $proposition instanceof NotEqualTo => $this->serializeBinary($proposition, '!='),
            $proposition instanceof GreaterThan => $this->serializeBinary($proposition, '>'),
            $proposition instanceof GreaterThanOrEqualTo => $this->serializeBinary($proposition, '>='),
            $proposition instanceof LessThan => $this->serializeBinary($proposition, '<'),
            $proposition instanceof LessThanOrEqualTo => $this->serializeBinary($proposition, '<='),
            $proposition instanceof In => $this->serializeIn($proposition, false),
            $proposition instanceof NotIn => $this->serializeIn($proposition, true),
            $proposition instanceof Between => $this->serializeBetween($proposition),
            // Logical operators
            $proposition instanceof LogicalAnd => $this->serializeLogical($proposition, 'AND'),
            $proposition instanceof LogicalOr => $this->serializeLogical($proposition, 'OR'),
            $proposition instanceof LogicalNot => $this->serializeNot($proposition),
            // String operators (LIKE/NOT LIKE)
            $proposition instanceof Matches => $this->serializeLike($proposition, false),
            $proposition instanceof DoesNotMatch => $this->serializeLike($proposition, true),
            default => throw new LogicException(sprintf('Unsupported operator: %s', $proposition::class)),
        };
    }

    /**
     * Serialize a binary operator.
     *
     * @param  Proposition $proposition The proposition containing two operands
     * @param  string      $operator    The operator symbol
     * @return string      The serialized binary expression
     */
    private function serializeBinary(Proposition $proposition, string $operator): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, sprintf('Binary operator %s requires exactly 2 operands', $operator));

        $left = $this->serializeOperand($operands[0]);
        $right = $this->serializeOperand($operands[1]);

        // Special handling for IS NULL / IS NOT NULL
        if ($right === 'NULL' && ($operator === '=' || $operator === '!=')) {
            return $operator === '='
                ? sprintf('%s IS NULL', $left)
                : sprintf('%s IS NOT NULL', $left);
        }

        return sprintf('%s %s %s', $left, $operator, $right);
    }

    /**
     * Serialize a logical operator (AND, OR).
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
                : $this->serializeOperand($operand),
            $operands,
        );

        return implode(' '.$operator.' ', $serialized);
    }

    /**
     * Serialize a NOT operator.
     *
     * @param  LogicalNot $not The NOT proposition
     * @return string     The serialized NOT expression
     */
    private function serializeNot(LogicalNot $not): string
    {
        $operands = self::getOperands($not);

        throw_if(count($operands) !== 1, LogicException::class, 'NOT operator requires exactly 1 operand');

        $operand = $operands[0];

        // Special handling for IS NOT NULL (represented as NOT(IS NULL))
        if ($operand instanceof EqualTo) {
            $innerOperands = self::getOperands($operand);

            if (count($innerOperands) === 2) {
                $right = $innerOperands[1];

                if ($right instanceof Variable && $right->getValue() === null) {
                    $left = $this->serializeOperand($innerOperands[0]);

                    return sprintf('%s IS NOT NULL', $left);
                }
            }
        }

        if ($operand instanceof Proposition) {
            return sprintf('NOT (%s)', $this->serializeProposition($operand));
        }

        return sprintf('NOT %s', $this->serializeOperand($operand));
    }

    /**
     * Serialize an IN or NOT IN operator.
     *
     * @param  In|NotIn $proposition The IN proposition
     * @param  bool     $negated     Whether this is NOT IN
     * @return string   The serialized IN expression
     */
    private function serializeIn(In|NotIn $proposition, bool $negated): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, 'IN operator requires exactly 2 operands');

        $field = $this->serializeOperand($operands[0]);
        $values = $operands[1];

        // Extract array from Variable
        $array = $values instanceof Variable ? $values->getValue() : $values;

        throw_unless(is_array($array), LogicException::class, 'IN operator requires array of values');

        $serializedValues = array_map(
            fn ($value): string => $this->serializeValue($value),
            $array,
        );

        $valuesList = sprintf('(%s)', implode(', ', $serializedValues));
        $operator = $negated ? 'NOT IN' : 'IN';

        return sprintf('%s %s %s', $field, $operator, $valuesList);
    }

    /**
     * Serialize a BETWEEN operator.
     *
     * @param  Between $proposition The BETWEEN proposition
     * @return string  The serialized BETWEEN expression
     */
    private function serializeBetween(Between $proposition): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 3, LogicException::class, 'BETWEEN operator requires exactly 3 operands');

        $field = $this->serializeOperand($operands[0]);
        $min = $this->serializeOperand($operands[1]);
        $max = $this->serializeOperand($operands[2]);

        return sprintf('%s BETWEEN %s AND %s', $field, $min, $max);
    }

    /**
     * Serialize a LIKE or NOT LIKE operator.
     *
     * @param  DoesNotMatch|Matches $proposition The LIKE proposition
     * @param  bool                 $negated     Whether this is NOT LIKE
     * @return string               The serialized LIKE expression
     */
    private function serializeLike(Matches|DoesNotMatch $proposition, bool $negated): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, 'LIKE operator requires exactly 2 operands');

        $field = $this->serializeOperand($operands[0]);
        $pattern = $operands[1];

        // Extract regex from Variable and convert back to SQL LIKE pattern
        $regex = $pattern instanceof Variable ? $pattern->getValue() : $pattern;

        throw_unless(is_string($regex), LogicException::class, 'LIKE pattern must be a string');

        // Convert regex back to SQL LIKE pattern
        $sqlPattern = self::regexToLikePattern($regex);
        $operator = $negated ? 'NOT LIKE' : 'LIKE';

        return sprintf('%s %s %s', $field, $operator, $this->serializeValue($sqlPattern));
    }

    /**
     * Convert regex pattern back to SQL LIKE pattern.
     *
     * @param  string $regex The regex pattern
     * @return string The SQL LIKE pattern
     */
    private static function regexToLikePattern(string $regex): string
    {
        // Remove anchors: /^...$/
        if (preg_match('/^\/\^(.+)\$\/$/', $regex, $matches)) {
            $pattern = $matches[1];
        } else {
            return $regex;
        }

        // Convert regex back to SQL LIKE
        // .* -> %
        // . -> _
        // Escaped characters -> literal
        $result = '';
        $length = mb_strlen($pattern);
        $i = 0;

        while ($i < $length) {
            $char = $pattern[$i];
            $nextChar = $i + 1 < $length ? $pattern[$i + 1] : null;

            if ($char === '.' && $nextChar === '*') {
                $result .= '%';
                $i += 2;
            } elseif ($char === '.' && $nextChar !== '*') {
                $result .= '_';
                ++$i;
            } elseif ($char === '\\' && $nextChar !== null) {
                // Handle escaped characters
                if ($nextChar === '%' || $nextChar === '_') {
                    $result .= '\\'.$nextChar;
                } else {
                    $result .= $nextChar;
                }

                $i += 2;
            } else {
                $result .= $char;
                ++$i;
            }
        }

        return $result;
    }

    /**
     * Serialize an operand (Variable or value).
     *
     * @param  mixed  $operand The operand to serialize
     * @return string The serialized operand
     */
    private function serializeOperand(mixed $operand): string
    {
        // Handle Propositions recursively
        if ($operand instanceof Proposition) {
            return '('.$this->serializeProposition($operand).')';
        }

        // Handle Variables
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            return $this->serializeVariable($operand);
        }

        // Handle raw values
        return $this->serializeValue($operand);
    }

    /**
     * Serialize a Variable.
     *
     * @param  BuilderVariable|Variable $variable The variable to serialize
     * @return string                   The serialized variable name or value
     */
    private function serializeVariable(Variable|BuilderVariable $variable): string
    {
        $name = $variable->getName();

        // If variable has a name, it's a field reference
        if ($name !== null) {
            return $name;
        }

        // Otherwise, serialize its value
        return $this->serializeValue($variable->getValue());
    }

    /**
     * Serialize a raw value (string, number, boolean, null, array).
     *
     * @param  mixed  $value The value to serialize
     * @return string The serialized value
     */
    private function serializeValue(mixed $value): string
    {
        if (is_string($value)) {
            // SQL uses single quotes for strings
            $escaped = str_replace("'", "''", $value);

            return sprintf("'%s'", $escaped);
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (null === $value) {
            return 'NULL';
        }

        if (is_array($value)) {
            $elements = array_map(
                fn ($item): string => $this->serializeValue($item),
                array_values($value), // Re-index to remove keys
            );

            return sprintf('(%s)', implode(', ', $elements));
        }

        // Fallback for objects or other types
        if (is_numeric($value)) {
            return (string) $value;
        }

        throw new LogicException(sprintf('Cannot cast value to string: %s', gettype($value)));
    }

    /**
     * Wrap expression in parentheses if needed based on operator precedence.
     *
     * SQL operator precedence (high to low): NOT, AND, OR
     * We need to wrap lower precedence operators when they appear as children of higher precedence operators.
     *
     * @param  Proposition $proposition The proposition to potentially wrap
     * @param  string      $parentOp    The parent operator
     * @return string      The wrapped or unwrapped expression
     */
    private function wrapIfNeeded(Proposition $proposition, string $parentOp): string
    {
        $needsWrap = match (true) {
            // Wrap AND inside OR: (a AND b) OR c
            $proposition instanceof LogicalAnd && $parentOp === 'OR' => true,
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
