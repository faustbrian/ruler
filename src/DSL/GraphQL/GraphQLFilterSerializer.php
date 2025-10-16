<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Core\Rule;
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
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringContainsInsensitive;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Operators\String\StringDoesNotContainInsensitive;
use Cline\Ruler\Operators\Type\IsArray;
use Cline\Ruler\Operators\Type\IsBoolean;
use Cline\Ruler\Operators\Type\IsNull;
use Cline\Ruler\Operators\Type\IsNumeric;
use Cline\Ruler\Operators\Type\IsString;
use Cline\Ruler\Variables\Variable;
use LogicException;
use ReflectionClass;

use function array_map;
use function array_merge;
use function count;
use function is_array;
use function is_bool;
use function is_null;
use function is_numeric;
use function is_string;
use function sprintf;
use function trim;

/**
 * Serializes Rule objects back to GraphQL Filter DSL syntax.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to GraphQL filter object syntax. Supports all GraphQL filter operators
 * including comparison, logical, string, list, null check, and type validation operations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $serializer = new GraphQLFilterSerializer();
 * $parser = new GraphQLFilterParser();
 *
 * $rule = $parser->parse(['age' => ['gte' => 18], 'country' => 'US']);
 * $filter = $serializer->serialize($rule);
 * // Returns: ['age' => ['gte' => 18], 'country' => ['eq' => 'US']]
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see GraphQLFilterParser For parsing filter arrays into Rules
 * @see GraphQLFilterValidator For validating filter arrays
 *
 * @psalm-immutable
 */
final readonly class GraphQLFilterSerializer
{
    /**
     * Serialize a Rule to a GraphQL Filter DSL array.
     *
     * Walks the Rule's Proposition tree and reconstructs the original
     * GraphQL filter object syntax. Supports implicit AND for multiple
     * field conditions and explicit logical operators.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return array<string, mixed> The GraphQL filter array
     */
    public function serialize(Rule $rule): array
    {
        $reflection = new ReflectionClass($rule);
        $conditionProperty = $reflection->getProperty('condition');
        $conditionProperty->setAccessible(true);

        /** @var Proposition $condition */
        $condition = $conditionProperty->getValue($rule);

        return $this->serializeProposition($condition);
    }

    /**
     * Serialize a Proposition to GraphQL filter syntax.
     *
     * @param Proposition $proposition The proposition to serialize
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeProposition(Proposition $proposition): array
    {
        return match (true) {
            // Logical operators
            $proposition instanceof LogicalAnd => $this->serializeAnd($proposition),
            $proposition instanceof LogicalOr => $this->serializeOr($proposition),
            $proposition instanceof LogicalNot => $this->serializeNot($proposition),

            // Comparison operators
            $proposition instanceof EqualTo => $this->serializeComparison($proposition, 'eq'),
            $proposition instanceof NotEqualTo => $this->serializeComparison($proposition, 'ne'),
            $proposition instanceof GreaterThan => $this->serializeComparison($proposition, 'gt'),
            $proposition instanceof GreaterThanOrEqualTo => $this->serializeComparison($proposition, 'gte'),
            $proposition instanceof LessThan => $this->serializeComparison($proposition, 'lt'),
            $proposition instanceof LessThanOrEqualTo => $this->serializeComparison($proposition, 'lte'),

            // List operators
            $proposition instanceof In => $this->serializeList($proposition, 'in'),
            $proposition instanceof NotIn => $this->serializeList($proposition, 'notIn'),

            // String operators
            $proposition instanceof StringContains => $this->serializeString($proposition, 'contains'),
            $proposition instanceof StringContainsInsensitive => $this->serializeString($proposition, 'containsInsensitive'),
            $proposition instanceof StringDoesNotContain => $this->serializeString($proposition, 'notContains'),
            $proposition instanceof StringDoesNotContainInsensitive => $this->serializeString($proposition, 'notContainsInsensitive'),
            $proposition instanceof StartsWith => $this->serializeString($proposition, 'startsWith'),
            $proposition instanceof EndsWith => $this->serializeString($proposition, 'endsWith'),
            $proposition instanceof Matches => $this->serializeMatches($proposition),

            // Type operators
            $proposition instanceof IsNull => $this->serializeNull($proposition),
            $proposition instanceof IsString => $this->serializeType($proposition, 'string'),
            $proposition instanceof IsNumeric => $this->serializeType($proposition, 'numeric'),
            $proposition instanceof IsBoolean => $this->serializeType($proposition, 'boolean'),
            $proposition instanceof IsArray => $this->serializeType($proposition, 'array'),

            default => throw new LogicException(sprintf('Unsupported operator: %s', $proposition::class)),
        };
    }

    /**
     * Serialize an AND logical operator.
     *
     * @param LogicalAnd $and The AND proposition
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeAnd(LogicalAnd $and): array
    {
        $operands = $this->getOperands($and);

        // Check if all operands are field comparisons (can be implicit AND)
        $canBeImplicit = true;
        $fields = [];

        foreach ($operands as $operand) {
            if (!$operand instanceof Proposition) {
                $canBeImplicit = false;

                break;
            }

            $serialized = $this->serializeProposition($operand);

            // Check if it's a simple field condition (single key-value)
            if (count($serialized) === 1 && !isset($serialized['AND']) && !isset($serialized['OR']) && !isset($serialized['NOT'])) {
                $fieldName = array_key_first($serialized);
                $fields[$fieldName] = $serialized[$fieldName];
            } else {
                $canBeImplicit = false;

                break;
            }
        }

        // If can be implicit, return merged fields
        if ($canBeImplicit && count($fields) > 0) {
            return $fields;
        }

        // Otherwise, use explicit AND
        $conditions = array_map(
            fn ($operand) => $this->serializeProposition($operand),
            $operands,
        );

        return ['AND' => $conditions];
    }

    /**
     * Serialize an OR logical operator.
     *
     * @param LogicalOr $or The OR proposition
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeOr(LogicalOr $or): array
    {
        $operands = $this->getOperands($or);

        $conditions = array_map(
            fn ($operand) => $this->serializeProposition($operand),
            $operands,
        );

        return ['OR' => $conditions];
    }

    /**
     * Serialize a NOT logical operator.
     *
     * @param LogicalNot $not The NOT proposition
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeNot(LogicalNot $not): array
    {
        $operands = $this->getOperands($not);

        if (count($operands) !== 1) {
            throw new LogicException('NOT operator requires exactly 1 operand');
        }

        return ['NOT' => $this->serializeProposition($operands[0])];
    }

    /**
     * Serialize a comparison operator.
     *
     * @param Proposition $proposition The comparison proposition
     * @param string      $operator    The GraphQL operator name
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeComparison(Proposition $proposition, string $operator): array
    {
        $operands = $this->getOperands($proposition);

        if (count($operands) !== 2) {
            throw new LogicException(sprintf('Comparison operator %s requires exactly 2 operands', $operator));
        }

        $fieldName = $this->extractFieldName($operands[0]);
        $value = $this->extractValue($operands[1]);

        return [$fieldName => [$operator => $value]];
    }

    /**
     * Serialize a list operator (in, notIn).
     *
     * @param Proposition $proposition The list proposition
     * @param string      $operator    The GraphQL operator name
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeList(Proposition $proposition, string $operator): array
    {
        $operands = $this->getOperands($proposition);

        if (count($operands) !== 2) {
            throw new LogicException(sprintf('List operator %s requires exactly 2 operands', $operator));
        }

        $fieldName = $this->extractFieldName($operands[0]);
        $value = $this->extractValue($operands[1]);

        return [$fieldName => [$operator => $value]];
    }

    /**
     * Serialize a string operator.
     *
     * @param Proposition $proposition The string proposition
     * @param string      $operator    The GraphQL operator name
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeString(Proposition $proposition, string $operator): array
    {
        $operands = $this->getOperands($proposition);

        if (count($operands) !== 2) {
            throw new LogicException(sprintf('String operator %s requires exactly 2 operands', $operator));
        }

        $fieldName = $this->extractFieldName($operands[0]);
        $value = $this->extractValue($operands[1]);

        return [$fieldName => [$operator => $value]];
    }

    /**
     * Serialize a matches (regex) operator.
     *
     * @param Matches $matches The matches proposition
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeMatches(Matches $matches): array
    {
        $operands = $this->getOperands($matches);

        if (count($operands) !== 2) {
            throw new LogicException('Matches operator requires exactly 2 operands');
        }

        $fieldName = $this->extractFieldName($operands[0]);
        $pattern = $this->extractValue($operands[1]);

        // Remove regex delimiters if present
        if (is_string($pattern) && str_starts_with($pattern, '/') && str_ends_with($pattern, '/')) {
            $pattern = trim($pattern, '/');
        }

        return [$fieldName => ['match' => $pattern]];
    }

    /**
     * Serialize a null check operator.
     *
     * @param IsNull $isNull The null check proposition
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeNull(IsNull $isNull): array
    {
        $operands = $this->getOperands($isNull);

        if (count($operands) !== 1) {
            throw new LogicException('IsNull operator requires exactly 1 operand');
        }

        $fieldName = $this->extractFieldName($operands[0]);

        return [$fieldName => ['isNull' => true]];
    }

    /**
     * Serialize a type validation operator.
     *
     * @param Proposition $proposition The type proposition
     * @param string      $typeName    The type name
     *
     * @return array<string, mixed> The serialized filter
     */
    private function serializeType(Proposition $proposition, string $typeName): array
    {
        $operands = $this->getOperands($proposition);

        if (count($operands) !== 1) {
            throw new LogicException(sprintf('Type operator %s requires exactly 1 operand', $typeName));
        }

        $fieldName = $this->extractFieldName($operands[0]);

        return [$fieldName => ['type' => $typeName]];
    }

    /**
     * Extract field name from a Variable operand.
     *
     * @param mixed $operand The operand to extract field name from
     *
     * @return string The field name
     */
    private function extractFieldName(mixed $operand): string
    {
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            $name = $operand->getName();

            if ($name === null) {
                throw new LogicException('Variable must have a name');
            }

            return $name;
        }

        throw new LogicException('Expected Variable operand');
    }

    /**
     * Extract value from a Variable operand.
     *
     * @param mixed $operand The operand to extract value from
     *
     * @return mixed The extracted value
     */
    private function extractValue(mixed $operand): mixed
    {
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            return $operand->getValue();
        }

        return $operand;
    }

    /**
     * Get operands from an operator using reflection.
     *
     * @param object $operator The operator object
     *
     * @return array<int, mixed> The operands
     */
    private function getOperands(object $operator): array
    {
        $reflection = new ReflectionClass($operator);

        // Try to get operands property
        if ($reflection->hasProperty('operands')) {
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setAccessible(true);

            return $operandsProperty->getValue($operator);
        }

        // Fallback: call getOperands() if it exists
        if ($reflection->hasMethod('getOperands')) {
            return $operator->getOperands();
        }

        return [];
    }
}
