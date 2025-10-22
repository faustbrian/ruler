<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\MongoDB;

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
use Cline\Ruler\Operators\Comparison\NotSameAs;
use Cline\Ruler\Operators\Comparison\SameAs;
use Cline\Ruler\Operators\Date\After;
use Cline\Ruler\Operators\Date\Before;
use Cline\Ruler\Operators\Date\IsBetweenDates;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNand;
use Cline\Ruler\Operators\Logical\LogicalNor;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\Logical\LogicalXor;
use Cline\Ruler\Operators\String\DoesNotMatch;
use Cline\Ruler\Operators\String\EndsWith;
use Cline\Ruler\Operators\String\EndsWithInsensitive;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StartsWithInsensitive;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Operators\String\StringContainsInsensitive;
use Cline\Ruler\Operators\String\StringDoesNotContain;
use Cline\Ruler\Operators\String\StringDoesNotContainInsensitive;
use Cline\Ruler\Operators\String\StringLength;
use Cline\Ruler\Operators\Type\ArrayCount;
use Cline\Ruler\Operators\Type\IsArray;
use Cline\Ruler\Operators\Type\IsBoolean;
use Cline\Ruler\Operators\Type\IsEmpty;
use Cline\Ruler\Operators\Type\IsNull;
use Cline\Ruler\Operators\Type\IsNumeric;
use Cline\Ruler\Operators\Type\IsString;
use Cline\Ruler\Variables\Variable;
use LogicException;
use ReflectionClass;

use const JSON_THROW_ON_ERROR;

use function array_map;
use function array_values;
use function count;
use function get_debug_type;
use function is_array;
use function is_string;
use function json_encode;
use function method_exists;
use function preg_match;
use function sprintf;
use function throw_if;

/**
 * Serializes Rule objects back to MongoDB Query DSL documents.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to MongoDB query document format (JSON). Supports all MongoDB operators
 * including comparison, logical, string, date, and type operations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $serializer = new MongoQuerySerializer();
 * $parser = new MongoQueryParser();
 *
 * $rule = $parser->parse(['age' => ['$gte' => 18]]);
 * $json = $serializer->serialize($rule);
 * // Returns: '{"age":{"$gte":18}}'
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see MongoQueryParser For parsing MongoDB query documents into Rules
 * @see MongoQueryValidator For validating MongoDB query documents
 *
 * @psalm-immutable
 */
final readonly class MongoQuerySerializer
{
    /**
     * Serialize a Rule to a MongoDB query JSON string.
     *
     * Walks the Rule's Proposition tree and reconstructs the MongoDB
     * query document as a JSON string.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return string The MongoDB query JSON string
     */
    public function serialize(Rule $rule): string
    {
        $reflection = new ReflectionClass($rule);
        $conditionProperty = $reflection->getProperty('condition');

        /** @var Proposition $condition */
        $condition = $conditionProperty->getValue($rule);

        $document = $this->serializeProposition($condition);

        return json_encode($document, JSON_THROW_ON_ERROR);
    }

    /**
     * Serialize a Rule to a MongoDB query array document.
     *
     * Walks the Rule's Proposition tree and reconstructs the MongoDB
     * query document as an associative array.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return array<string, mixed> The MongoDB query document array
     */
    public function serializeToArray(Rule $rule): array
    {
        $reflection = new ReflectionClass($rule);
        $conditionProperty = $reflection->getProperty('condition');

        /** @var Proposition $condition */
        $condition = $conditionProperty->getValue($rule);

        return $this->serializeProposition($condition);
    }

    /**
     * Serialize a Proposition to MongoDB query document structure.
     *
     * @param  Proposition          $proposition The proposition to serialize
     * @return array<string, mixed> The serialized query document fragment
     */
    private function serializeProposition(Proposition $proposition): array
    {
        // Special case: LogicalNot(EqualTo(field, null)) => {field: {$exists: true}}
        if ($proposition instanceof LogicalNot) {
            $operands = self::getOperands($proposition);

            if (count($operands) === 1 && $operands[0] instanceof EqualTo) {
                $eqOperands = self::getOperands($operands[0]);

                if (count($eqOperands) === 2 && null === self::extractValue($eqOperands[1])) {
                    $field = $this->extractFieldName($eqOperands[0]);

                    return [$field => ['$exists' => true]];
                }
            }
        }

        return match (true) {
            // Logical operators
            $proposition instanceof LogicalAnd => $this->serializeAnd($proposition),
            $proposition instanceof LogicalOr => $this->serializeOr($proposition),
            $proposition instanceof LogicalNor => $this->serializeNor($proposition),
            $proposition instanceof LogicalNot => $this->serializeNot($proposition),
            $proposition instanceof LogicalXor => $this->serializeXor($proposition),
            $proposition instanceof LogicalNand => $this->serializeNand($proposition),
            // Comparison operators
            $proposition instanceof EqualTo => $this->serializeEqualTo($proposition),
            $proposition instanceof NotEqualTo => $this->serializeFieldOperator($proposition, '$ne'),
            $proposition instanceof SameAs => $this->serializeFieldOperator($proposition, '$same'),
            $proposition instanceof NotSameAs => $this->serializeFieldOperator($proposition, '$nsame'),
            $proposition instanceof GreaterThan => $this->serializeFieldOperator($proposition, '$gt'),
            $proposition instanceof GreaterThanOrEqualTo => $this->serializeFieldOperator($proposition, '$gte'),
            $proposition instanceof LessThan => $this->serializeFieldOperator($proposition, '$lt'),
            $proposition instanceof LessThanOrEqualTo => $this->serializeFieldOperator($proposition, '$lte'),
            $proposition instanceof In => $this->serializeFieldOperator($proposition, '$in'),
            $proposition instanceof NotIn => $this->serializeFieldOperator($proposition, '$nin'),
            $proposition instanceof Between => $this->serializeBetween($proposition),
            // String operators
            $proposition instanceof Matches => $this->serializeRegex($proposition),
            $proposition instanceof DoesNotMatch => $this->serializeFieldOperator($proposition, '$notRegex'),
            $proposition instanceof StartsWith => $this->serializeFieldOperator($proposition, '$startsWith'),
            $proposition instanceof StartsWithInsensitive => $this->serializeFieldOperator($proposition, '$startsWithi'),
            $proposition instanceof EndsWith => $this->serializeFieldOperator($proposition, '$endsWith'),
            $proposition instanceof EndsWithInsensitive => $this->serializeFieldOperator($proposition, '$endsWithi'),
            $proposition instanceof StringContains => $this->serializeFieldOperator($proposition, '$contains'),
            $proposition instanceof StringContainsInsensitive => $this->serializeFieldOperator($proposition, '$containsi'),
            $proposition instanceof StringDoesNotContain => $this->serializeFieldOperator($proposition, '$notContains'),
            $proposition instanceof StringDoesNotContainInsensitive => $this->serializeFieldOperator($proposition, '$notContainsi'),
            // Date operators
            $proposition instanceof After => $this->serializeFieldOperator($proposition, '$after'),
            $proposition instanceof Before => $this->serializeFieldOperator($proposition, '$before'),
            $proposition instanceof IsBetweenDates => $this->serializeBetweenDates($proposition),
            // Type operators
            $proposition instanceof IsNull => $this->serializeIsNull($proposition),
            $proposition instanceof IsArray => $this->serializeTypeCheck($proposition, 'array'),
            $proposition instanceof IsBoolean => $this->serializeTypeCheck($proposition, 'boolean'),
            $proposition instanceof IsNumeric => $this->serializeTypeCheck($proposition, 'number'),
            $proposition instanceof IsString => $this->serializeTypeCheck($proposition, 'string'),
            $proposition instanceof IsEmpty => $this->serializeEmpty($proposition),
            default => throw new LogicException(sprintf('Unsupported operator: %s', $proposition::class)),
        };
    }

    /**
     * Serialize a LogicalAnd to $and operator.
     *
     * @param  LogicalAnd           $and The AND proposition
     * @return array<string, mixed> The $and query fragment
     */
    private function serializeAnd(LogicalAnd $and): array
    {
        $operands = self::getOperands($and);
        $conditions = array_map(
            function ($operand): array {
                throw_if(!$operand instanceof Proposition, LogicException::class, 'AND operand must be a Proposition');

                return $this->serializeProposition($operand);
            },
            $operands,
        );

        return ['$and' => $conditions];
    }

    /**
     * Serialize a LogicalOr to $or operator.
     *
     * @param  LogicalOr            $or The OR proposition
     * @return array<string, mixed> The $or query fragment
     */
    private function serializeOr(LogicalOr $or): array
    {
        $operands = self::getOperands($or);
        $conditions = array_map(
            function ($operand): array {
                throw_if(!$operand instanceof Proposition, LogicException::class, 'OR operand must be a Proposition');

                return $this->serializeProposition($operand);
            },
            $operands,
        );

        return ['$or' => $conditions];
    }

    /**
     * Serialize a LogicalNor to $nor operator.
     *
     * @param  LogicalNor           $nor The NOR proposition
     * @return array<string, mixed> The $nor query fragment
     */
    private function serializeNor(LogicalNor $nor): array
    {
        $operands = self::getOperands($nor);
        $conditions = array_map(
            function ($operand): array {
                throw_if(!$operand instanceof Proposition, LogicException::class, 'NOR operand must be a Proposition');

                return $this->serializeProposition($operand);
            },
            $operands,
        );

        return ['$nor' => $conditions];
    }

    /**
     * Serialize a LogicalNot to $not operator.
     *
     * @param  LogicalNot           $not The NOT proposition
     * @return array<string, mixed> The $not query fragment
     */
    private function serializeNot(LogicalNot $not): array
    {
        $operands = self::getOperands($not);

        throw_if(count($operands) !== 1, LogicException::class, 'NOT operator requires exactly 1 operand');

        $operand = $operands[0];
        throw_if(!$operand instanceof Proposition, LogicException::class, 'NOT operand must be a Proposition');

        return ['$not' => $this->serializeProposition($operand)];
    }

    /**
     * Serialize a LogicalXor to $xor operator.
     *
     * @param  LogicalXor           $xor The XOR proposition
     * @return array<string, mixed> The $xor query fragment
     */
    private function serializeXor(LogicalXor $xor): array
    {
        $operands = self::getOperands($xor);
        $conditions = array_map(
            function ($operand): array {
                throw_if(!$operand instanceof Proposition, LogicException::class, 'XOR operand must be a Proposition');

                return $this->serializeProposition($operand);
            },
            $operands,
        );

        return ['$xor' => $conditions];
    }

    /**
     * Serialize a LogicalNand to $nand operator.
     *
     * @param  LogicalNand          $nand The NAND proposition
     * @return array<string, mixed> The $nand query fragment
     */
    private function serializeNand(LogicalNand $nand): array
    {
        $operands = self::getOperands($nand);
        $conditions = array_map(
            function ($operand): array {
                throw_if(!$operand instanceof Proposition, LogicException::class, 'NAND operand must be a Proposition');

                return $this->serializeProposition($operand);
            },
            $operands,
        );

        return ['$nand' => $conditions];
    }

    /**
     * Serialize an EqualTo operator.
     *
     * Handles special case of null comparison for $exists: false.
     *
     * @param  EqualTo              $equalTo The EqualTo proposition
     * @return array<string, mixed> The field query fragment
     */
    private function serializeEqualTo(EqualTo $equalTo): array
    {
        $operands = self::getOperands($equalTo);

        throw_if(count($operands) !== 2, LogicException::class, 'EqualTo operator requires exactly 2 operands');

        $field = $this->extractFieldName($operands[0]);
        $value = self::extractValue($operands[1]);

        // Special case: {field: {$eq: null}} => {field: {$exists: false}}
        if (null === $value) {
            return [$field => ['$exists' => false]];
        }

        // Use implicit equality for all other values
        return [$field => $value];
    }

    /**
     * Serialize a field-based operator.
     *
     * @param  Proposition          $proposition The proposition
     * @param  string               $operator    The MongoDB operator
     * @return array<string, mixed> The field query fragment
     */
    private function serializeFieldOperator(Proposition $proposition, string $operator): array
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, sprintf('Binary operator %s requires exactly 2 operands', $operator));

        $field = $this->extractFieldName($operands[0]);
        $value = self::extractValue($operands[1]);

        return [$field => [$operator => $value]];
    }

    /**
     * Serialize a Between operator to $between.
     *
     * @param  Between              $between The Between proposition
     * @return array<string, mixed> The $between query fragment
     */
    private function serializeBetween(Between $between): array
    {
        $operands = self::getOperands($between);

        throw_if(count($operands) !== 3, LogicException::class, 'Between operator requires exactly 3 operands');

        $field = $this->extractFieldName($operands[0]);
        $min = self::extractValue($operands[1]);
        $max = self::extractValue($operands[2]);

        return [$field => ['$between' => [$min, $max]]];
    }

    /**
     * Serialize an IsBetweenDates operator to $betweenDates.
     *
     * @param  IsBetweenDates       $betweenDates The IsBetweenDates proposition
     * @return array<string, mixed> The $betweenDates query fragment
     */
    private function serializeBetweenDates(IsBetweenDates $betweenDates): array
    {
        $operands = self::getOperands($betweenDates);

        throw_if(count($operands) !== 3, LogicException::class, 'BetweenDates operator requires exactly 3 operands');

        $field = $this->extractFieldName($operands[0]);
        $start = self::extractValue($operands[1]);
        $end = self::extractValue($operands[2]);

        return [$field => ['$betweenDates' => [$start, $end]]];
    }

    /**
     * Serialize a Matches operator to $regex.
     *
     * @param  Matches              $matches The Matches proposition
     * @return array<string, mixed> The $regex query fragment
     */
    private function serializeRegex(Matches $matches): array
    {
        $operands = self::getOperands($matches);

        throw_if(count($operands) !== 2, LogicException::class, 'Matches operator requires exactly 2 operands');

        $field = $this->extractFieldName($operands[0]);
        $pattern = self::extractValue($operands[1]);

        // Extract pattern and flags from PHP regex format
        if (is_string($pattern) && preg_match('#^/(.+)/([imsux]*)$#', $pattern, $matches)) {
            $regexPattern = $matches[1];
            $flags = $matches[2];

            $result = [$field => ['$regex' => $regexPattern]];

            if ($flags !== '') {
                $result[$field]['$options'] = $flags;
            }

            return $result;
        }

        return [$field => ['$regex' => $pattern]];
    }

    /**
     * Serialize an IsNull operator to $exists: false.
     *
     * @param  IsNull               $isNull The IsNull proposition
     * @return array<string, mixed> The $exists query fragment
     */
    private function serializeIsNull(IsNull $isNull): array
    {
        $operands = self::getOperands($isNull);

        throw_if(count($operands) !== 1, LogicException::class, 'IsNull operator requires exactly 1 operand');

        $field = $this->extractFieldName($operands[0]);

        return [$field => ['$exists' => false]];
    }

    /**
     * Serialize an IsEmpty operator to $empty.
     *
     * @param  IsEmpty              $isEmpty The IsEmpty proposition
     * @return array<string, mixed> The $empty query fragment
     */
    private function serializeEmpty(IsEmpty $isEmpty): array
    {
        $operands = self::getOperands($isEmpty);

        throw_if(count($operands) !== 1, LogicException::class, 'IsEmpty operator requires exactly 1 operand');

        $field = $this->extractFieldName($operands[0]);

        return [$field => ['$empty' => true]];
    }

    /**
     * Serialize a type check operator to $type.
     *
     * @param  Proposition          $proposition The type check proposition
     * @param  string               $typeName    The MongoDB type name
     * @return array<string, mixed> The $type query fragment
     */
    private function serializeTypeCheck(Proposition $proposition, string $typeName): array
    {
        $operands = self::getOperands($proposition);

        throw_if(count($operands) !== 1, LogicException::class, 'Type check operator requires exactly 1 operand');

        $field = $this->extractFieldName($operands[0]);

        return [$field => ['$type' => $typeName]];
    }

    /**
     * Extract field name from a Variable.
     *
     * @param  mixed  $operand The operand (should be a Variable)
     * @return string The field name
     */
    private function extractFieldName(mixed $operand): string
    {
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            $name = $operand->getName();

            throw_if($name === null, LogicException::class, 'Variable must have a name to serialize as field');

            return $name;
        }

        // Handle special cases like StringLength, ArrayCount
        if ($operand instanceof StringLength) {
            $innerOperands = self::getOperands($operand);

            return $this->extractFieldName($innerOperands[0]);
        }

        if ($operand instanceof ArrayCount) {
            $innerOperands = self::getOperands($operand);

            return $this->extractFieldName($innerOperands[0]);
        }

        throw new LogicException(sprintf('Cannot extract field name from: %s', get_debug_type($operand)));
    }

    /**
     * Extract value from a Variable or literal.
     *
     * @param  mixed $operand The operand
     * @return mixed The extracted value
     */
    private static function extractValue(mixed $operand): mixed
    {
        // @phpstan-ignore instanceof.alwaysFalse (BuilderVariable can be mixed at runtime)
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            return $operand->getValue();
        }

        return $operand;
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
