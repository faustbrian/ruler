<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
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
use function method_exists;
use function preg_match;
use function sprintf;
use function str_replace;
use function throw_if;
use function throw_unless;

/**
 * Serializes Rule objects back to LDAP Filter DSL expression strings.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to human-readable LDAP filter syntax following RFC 4515. Supports all LDAP
 * operators including comparison (=, >=, <=, ~=), logical (&, |, !), and
 * special filters (presence, wildcard).
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * LDAP uses prefix notation where operators precede their operands:
 * - AND: (&(age>=18)(country=US))
 * - OR: (|(role=admin)(role=manager))
 * - NOT: (!(status=inactive))
 * - Presence: (email=*)
 * - Wildcard: (name=John*)
 *
 * Example usage:
 * ```php
 * $serializer = new LDAPFilterSerializer();
 * $parser = new LDAPFilterParser();
 *
 * $rule = $parser->parse('(&(age>=18)(country=US))');
 * $expression = $serializer->serialize($rule);
 * // Returns: '(&(age>=18)(country=US))'
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see LDAPFilterParser For parsing LDAP filter strings into Rules
 * @see LDAPFilterValidator For validating LDAP filter strings
 *
 * @psalm-immutable
 */
final readonly class LDAPFilterSerializer
{
    /**
     * Serialize a Rule to an LDAP Filter DSL expression string.
     *
     * Walks the Rule's Proposition tree and reconstructs the original
     * LDAP filter syntax using prefix notation with parentheses.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return string The LDAP filter expression
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
     * Serialize a comparison operator.
     *
     * Format: (attribute operator value)
     * Special cases:
     * - (attribute=*) for presence checks (NOT(field=null))
     *
     * @param  Proposition $proposition The comparison proposition
     * @param  string      $operator    The comparison operator symbol (=, >=, <=, >, <)
     * @return string      The serialized comparison expression
     */
    private static function serializeComparison(Proposition $proposition, string $operator): string
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, sprintf('Comparison operator %s requires exactly 2 operands', $operator));

        $attribute = self::extractAttributeName($operands[0]);
        $value = self::extractValue($operands[1]);

        // Special case: presence check (field=*)
        if ($operator === '=' && $value === null) {
            // This might be part of a NOT(field=null) pattern which becomes (field=*)
            // However, we need context from parent to determine this
            return sprintf('(%s=*)', $attribute);
        }

        $valueStr = self::formatValue($value);

        return sprintf('(%s%s%s)', $attribute, $operator, $valueStr);
    }

    /**
     * Serialize a Matches operator (wildcard or approximate match).
     *
     * Detects pattern type:
     * - /^literal$/i → (attribute~=literal) for approximate match
     * - /^.*pattern.*$/ → (attribute=*pattern*) for wildcard
     *
     * @param  Matches $matches The Matches proposition
     * @return string  The serialized match expression
     */
    private static function serializeMatches(Matches $matches): string
    {
        $operands = self::getOperands($matches);

        throw_if(count($operands) !== 2, LogicException::class, 'Matches operator requires exactly 2 operands');

        $attribute = self::extractAttributeName($operands[0]);
        $pattern = self::extractValue($operands[1]);

        throw_unless(is_string($pattern), LogicException::class, 'Matches pattern must be a string');

        // Detect approximate match (case-insensitive pattern without wildcards)
        if (preg_match('/^\/(.+)\/i$/', $pattern, $matches)) {
            $literalValue = $matches[1];

            // Remove escaping from preg_quote if present
            $literalValue = str_replace('\\', '', $literalValue);

            return sprintf('(%s~=%s)', $attribute, $literalValue);
        }

        // Detect wildcard pattern
        if (preg_match('/^\/\^(.*)\$\/$/', $pattern, $matches)) {
            $wildcardPattern = $matches[1];

            // Convert regex .* back to LDAP *
            $wildcardPattern = str_replace('.*', '*', $wildcardPattern);

            // Remove escaping from preg_quote
            $wildcardPattern = str_replace('\\', '', $wildcardPattern);

            return sprintf('(%s=%s)', $attribute, $wildcardPattern);
        }

        throw new LogicException(sprintf('Unsupported Matches pattern: %s', $pattern));
    }

    /**
     * Extract attribute name from a Variable operand.
     *
     * @param  mixed  $operand The operand to extract attribute name from
     * @return string The attribute name
     */
    private static function extractAttributeName(mixed $operand): string
    {
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            $name = $operand->getName();

            throw_if($name === null, LogicException::class, 'Variable must have a name for LDAP serialization');

            return $name;
        }

        throw new LogicException('Expected Variable operand for attribute name');
    }

    /**
     * Extract value from a Variable operand.
     *
     * @param  mixed $operand The operand to extract value from
     * @return mixed The extracted value
     */
    private static function extractValue(mixed $operand): mixed
    {
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            return $operand->getValue();
        }

        throw new LogicException('Expected Variable operand for value');
    }

    /**
     * Format a value for LDAP filter syntax.
     *
     * @param  mixed  $value The value to format
     * @return string The formatted value string
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

        throw new LogicException(sprintf('Unsupported value type for LDAP serialization: %s', gettype($value)));
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

    /**
     * Serialize a Proposition to LDAP filter syntax.
     *
     * @param  Proposition $proposition The proposition to serialize
     * @return string      The serialized LDAP filter expression
     */
    private function serializeProposition(Proposition $proposition): string
    {
        return match (true) {
            // Logical operators (prefix notation)
            $proposition instanceof LogicalAnd => $this->serializeLogical($proposition, '&'),
            $proposition instanceof LogicalOr => $this->serializeLogical($proposition, '|'),
            $proposition instanceof LogicalNot => $this->serializeNot($proposition),
            // Comparison operators
            $proposition instanceof EqualTo => self::serializeComparison($proposition, '='),
            $proposition instanceof GreaterThanOrEqualTo => self::serializeComparison($proposition, '>='),
            $proposition instanceof LessThanOrEqualTo => self::serializeComparison($proposition, '<='),
            $proposition instanceof GreaterThan => self::serializeComparison($proposition, '>'),
            $proposition instanceof LessThan => self::serializeComparison($proposition, '<'),
            // String operators (wildcard and approximate)
            $proposition instanceof Matches => self::serializeMatches($proposition),
            default => throw new LogicException(sprintf('Unsupported operator for LDAP serialization: %s', $proposition::class)),
        };
    }

    /**
     * Serialize a logical operator (AND, OR) in LDAP prefix notation.
     *
     * Format: (&(condition1)(condition2)...) or (|(condition1)(condition2)...)
     *
     * @param  Proposition $proposition The logical proposition
     * @param  string      $operator    The logical operator symbol (& or |)
     * @return string      The serialized logical expression
     */
    private function serializeLogical(Proposition $proposition, string $operator): string
    {
        $operands = self::getOperands($proposition);

        $serialized = array_map(
            fn ($operand): string => $operand instanceof Proposition
                ? $this->serializeProposition($operand)
                : throw new LogicException('LDAP logical operators require Proposition operands'),
            $operands,
        );

        return sprintf('(%s%s)', $operator, implode('', $serialized));
    }

    /**
     * Serialize a NOT operator in LDAP prefix notation.
     *
     * Format: (!(condition))
     * Special case: NOT(field=null) → (field=*) for presence checks
     *
     * @param  LogicalNot $not The NOT proposition
     * @return string     The serialized NOT expression
     */
    private function serializeNot(LogicalNot $not): string
    {
        $operands = self::getOperands($not);

        throw_if(count($operands) !== 1, LogicException::class, 'LDAP NOT operator requires exactly 1 operand');

        $operand = $operands[0];

        if ($operand instanceof Proposition) {
            // Special case: NOT(field=null) becomes (field=*) for presence check
            if ($operand instanceof EqualTo) {
                $eqOperands = self::getOperands($operand);

                if (count($eqOperands) === 2) {
                    $value = self::extractValue($eqOperands[1]);

                    if ($value === null) {
                        $attribute = self::extractAttributeName($eqOperands[0]);

                        return sprintf('(%s=*)', $attribute);
                    }
                }
            }

            return sprintf('(!%s)', $this->serializeProposition($operand));
        }

        throw new LogicException('LDAP NOT operator requires Proposition operand');
    }
}
