<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\MongoQuery;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
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
use InvalidArgumentException;

use function array_key_exists;
use function array_key_first;
use function array_map;
use function count;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function throw_if;
use function throw_unless;

/**
 * Compiles MongoDB-style query documents into Proposition objects.
 *
 * Translates MongoDB query syntax into the internal rule engine's Proposition
 * tree structure. Supports MongoDB's query operators including comparison,
 * logical, string, date, and type operators, along with extended custom operators
 * for advanced filtering capabilities.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class MongoQueryCompiler
{
    /**
     * Field resolver for mapping query fields to variables.
     */
    private FieldResolver $fieldResolver;

    /**
     * Create a new MongoDB query compiler.
     *
     * @param RuleBuilder $ruleBuilder rule builder instance used to resolve field references
     *                                 and manage variable context during query compilation
     */
    public function __construct(RuleBuilder $ruleBuilder)
    {
        $this->fieldResolver = new FieldResolver($ruleBuilder);
    }

    /**
     * Compile a MongoDB query document into a Proposition.
     *
     * @param  array<string, mixed> $query MongoDB-style query document with fields and operators
     * @return Proposition          Compiled proposition tree ready for evaluation
     */
    public function compile(array $query): Proposition
    {
        return $this->compileQuery($query);
    }

    /**
     * Compile a $between operator for range checking.
     *
     * @param Variable              $field Field variable to test
     * @param array<int, float|int> $range Array containing exactly [min, max] values
     *
     * @throws InvalidArgumentException If range doesn't contain exactly 2 values
     *
     * @return Proposition Between proposition checking if field value falls within range
     */
    private static function compileBetween(Variable $field, array $range): Proposition
    {
        throw_if(count($range) !== 2, InvalidArgumentException::class, '$between requires exactly 2 values [min, max]');

        return new Between(
            $field,
            new Variable(null, $range[0]),
            new Variable(null, $range[1]),
        );
    }

    /**
     * Compile a $betweenDates operator for date range checking.
     *
     * @param Variable               $field Field variable containing a date value to test
     * @param array<int, int|string> $range Array containing exactly [start, end] date values
     *
     * @throws InvalidArgumentException If range doesn't contain exactly 2 values
     *
     * @return Proposition IsBetweenDates proposition checking if date falls within range
     */
    private static function compileBetweenDates(Variable $field, array $range): Proposition
    {
        throw_if(count($range) !== 2, InvalidArgumentException::class, '$betweenDates requires exactly 2 values [start, end]');

        return new IsBetweenDates(
            $field,
            new Variable(null, $range[0]),
            new Variable(null, $range[1]),
        );
    }

    /**
     * Compile a $exists operator to check field presence.
     *
     * @param  Variable    $field       Field variable to test for existence
     * @param  bool        $shouldExist True to check field exists, false to check it doesn't exist
     * @return Proposition Proposition checking field null status
     */
    private static function compileExists(Variable $field, bool $shouldExist): Proposition
    {
        $nullCheck = new EqualTo($field, new Variable(null, null));

        return $shouldExist
            ? new LogicalNot([$nullCheck])  // exists = NOT null
            : $nullCheck;                   // doesn't exist = IS null
    }

    /**
     * Compile a $regex operator for pattern matching.
     *
     * Converts MongoDB regex syntax to PHP PCRE format, translating option
     * flags (i=case-insensitive, m=multiline, s=dotall) to their PHP equivalents.
     *
     * @param  Variable    $field   Field variable to match against
     * @param  string      $pattern Regex pattern without delimiters
     * @param  string      $options MongoDB regex options string (i, m, s, etc.)
     * @return Proposition Proposition using PHP PCRE pattern
     */
    private static function compileRegex(Variable $field, string $pattern, string $options): Proposition
    {
        // Convert MongoDB regex to PHP regex
        $flags = '';

        if (str_contains($options, 'i')) {
            $flags .= 'i';
        }

        // case-insensitive
        if (str_contains($options, 'm')) {
            $flags .= 'm';
        }

        // multiline
        if (str_contains($options, 's')) {
            $flags .= 's';
        }

        // dotall

        $phpPattern = '/'.$pattern.'/'.$flags;

        return new Matches($field, new Variable(null, $phpPattern));
    }

    /**
     * Compile a $strLength operator to test string length.
     *
     * Supports both exact length matching (numeric value) and comparison operators
     * for range-based length testing (e.g., {"$gt": 10} for length > 10).
     *
     * @param Variable $field      Field variable containing string to measure
     * @param mixed    $comparison Either a number for exact match or comparison operator object
     *
     * @throws InvalidArgumentException If comparison is neither number nor valid operator object
     *
     * @return Proposition Proposition testing string length against the comparison
     */
    private static function compileStringLength(Variable $field, mixed $comparison): Proposition
    {
        // $strLength can be a number (exact match) or a comparison object
        if (is_numeric($comparison)) {
            return new EqualTo(
                new StringLength($field),
                new Variable(null, $comparison),
            );
        }

        if (is_array($comparison)) {
            // e.g. {"$gt": 10} - string length must be > 10
            $lengthVar = new StringLength($field);
            $conditions = [];

            foreach ($comparison as $operator => $operand) {
                $conditions[] = match ($operator) {
                    '$eq' => new EqualTo($lengthVar, new Variable(null, $operand)),
                    '$ne' => new NotEqualTo($lengthVar, new Variable(null, $operand)),
                    '$gt' => new GreaterThan($lengthVar, new Variable(null, $operand)),
                    '$gte' => new GreaterThanOrEqualTo($lengthVar, new Variable(null, $operand)),
                    '$lt' => new LessThan($lengthVar, new Variable(null, $operand)),
                    '$lte' => new LessThanOrEqualTo($lengthVar, new Variable(null, $operand)),
                    default => throw new InvalidArgumentException(sprintf('Unsupported $strLength operator: %s', $operator)),
                };
            }

            return count($conditions) === 1 ? $conditions[0] : new LogicalAnd($conditions);
        }

        throw new InvalidArgumentException('$strLength requires a number or comparison object');
    }

    /**
     * Compile a $type operator to check field data type.
     *
     * @param Variable $field Field variable to type-check
     * @param string   $type  Type name (null, array, bool, boolean, number, numeric, int, double, string)
     *
     * @throws InvalidArgumentException If type is not recognized
     *
     * @return Proposition Type-checking proposition
     */
    private static function compileType(Variable $field, string $type): Proposition
    {
        return match ($type) {
            'null' => new IsNull($field),
            'array' => new IsArray($field),
            'bool', 'boolean' => new IsBoolean($field),
            'number', 'numeric', 'int', 'double' => new IsNumeric($field),
            'string' => new IsString($field),
            default => throw new InvalidArgumentException('Unsupported type: '.$type),
        };
    }

    /**
     * Compile a $size operator to test array element count.
     *
     * Supports both exact count matching (numeric value) and comparison operators
     * for range-based count testing (e.g., {"$gt": 5} for more than 5 elements).
     *
     * @param Variable $field      Field variable containing array to count
     * @param mixed    $comparison Either a number for exact match or comparison operator object
     *
     * @throws InvalidArgumentException If comparison is neither number nor valid operator object
     *
     * @return Proposition Proposition testing array size against the comparison
     */
    private static function compileArrayCount(Variable $field, mixed $comparison): Proposition
    {
        // $size can be a number (exact match) or a comparison object
        if (is_numeric($comparison)) {
            return new EqualTo(
                new ArrayCount($field),
                new Variable(null, $comparison),
            );
        }

        if (is_array($comparison)) {
            // e.g. {"$gt": 5} - array size must be > 5
            $countVar = new ArrayCount($field);
            $conditions = [];

            foreach ($comparison as $operator => $operand) {
                $conditions[] = match ($operator) {
                    '$eq' => new EqualTo($countVar, new Variable(null, $operand)),
                    '$ne' => new NotEqualTo($countVar, new Variable(null, $operand)),
                    '$gt' => new GreaterThan($countVar, new Variable(null, $operand)),
                    '$gte' => new GreaterThanOrEqualTo($countVar, new Variable(null, $operand)),
                    '$lt' => new LessThan($countVar, new Variable(null, $operand)),
                    '$lte' => new LessThanOrEqualTo($countVar, new Variable(null, $operand)),
                    default => throw new InvalidArgumentException(sprintf('Unsupported $size operator: %s', $operator)),
                };
            }

            return count($conditions) === 1 ? $conditions[0] : new LogicalAnd($conditions);
        }

        throw new InvalidArgumentException('$size requires a number or comparison object');
    }

    /**
     * Compile a $empty operator to check if field is empty.
     *
     * @param  Variable    $field         Field variable to test for emptiness
     * @param  bool        $shouldBeEmpty True to check field is empty, false to check it's not empty
     * @return Proposition Proposition checking field empty status
     */
    private static function compileEmpty(Variable $field, bool $shouldBeEmpty): Proposition
    {
        $emptyCheck = new IsEmpty($field);

        return $shouldBeEmpty
            ? $emptyCheck
            : new LogicalNot([$emptyCheck]);
    }

    /**
     * Recursively compile a query document into a Proposition tree.
     *
     * @param array<string, mixed> $query Query document or sub-document to compile
     *
     * @throws InvalidArgumentException If query structure is invalid
     *
     * @return Proposition Compiled proposition representing the query logic
     */
    private function compileQuery(array $query): Proposition
    {
        // Empty query matches everything
        if ($query === []) {
            return new EqualTo(
                new Variable(null, true),
                new Variable(null, true),
            );
        }

        // Check for logical operators
        if (array_key_exists('$and', $query)) {
            /** @var array<int, array<string, mixed>> $conditions */
            $conditions = $query['$and'];

            return $this->compileAnd($conditions);
        }

        if (array_key_exists('$or', $query)) {
            /** @var array<int, array<string, mixed>> $conditions */
            $conditions = $query['$or'];

            return $this->compileOr($conditions);
        }

        if (array_key_exists('$nor', $query)) {
            /** @var array<int, array<string, mixed>> $conditions */
            $conditions = $query['$nor'];

            return $this->compileNor($conditions);
        }

        if (array_key_exists('$not', $query)) {
            /** @var array<string, mixed>|string $condition */
            $condition = $query['$not'];

            return $this->compileNot($condition);
        }

        if (array_key_exists('$xor', $query)) {
            /** @var array<int, array<string, mixed>> $conditions */
            $conditions = $query['$xor'];

            return $this->compileXor($conditions);
        }

        if (array_key_exists('$nand', $query)) {
            /** @var array<int, array<string, mixed>> $conditions */
            $conditions = $query['$nand'];

            return $this->compileNand($conditions);
        }

        // Implicit AND for multiple fields
        if (count($query) > 1) {
            $conditions = [];

            foreach ($query as $field => $value) {
                $conditions[] = $this->compileFieldCondition($field, $value);
            }

            return new LogicalAnd($conditions);
        }

        // Single field condition
        foreach ($query as $field => $value) {
            return $this->compileFieldCondition($field, $value);
        }

        // Unreachable: covered by empty check above
    }

    /**
     * Compile a single field condition into a Proposition.
     *
     * Handles both implicit equality (direct values) and explicit operators
     * (objects with $ prefixed keys). Multiple operators on the same field
     * are combined with implicit AND logic.
     *
     * @param string $field Field name to test
     * @param mixed  $value Value or operator object to compare against
     *
     * @throws InvalidArgumentException If operator is unsupported
     *
     * @return Proposition Compiled field condition proposition
     */
    private function compileFieldCondition(string $field, mixed $value): Proposition
    {
        $fieldVar = $this->fieldResolver->resolve($field);

        // Implicit equality (including empty arrays)
        if (!is_array($value) || $value === [] || !array_key_exists(0, $value) && !str_starts_with((string) array_key_first($value), '$')) {
            return new EqualTo($fieldVar, new Variable(null, $value));
        }

        // Operators object
        $conditions = [];

        foreach ($value as $operator => $operand) {
            // Skip $options - it's a modifier for $regex, not an operator
            if ($operator === '$options') {
                continue;
            }

            $conditions[] = match ($operator) {
                // Standard MongoDB comparison operators
                '$eq' => new EqualTo($fieldVar, new Variable(null, $operand)),
                '$ne' => new NotEqualTo($fieldVar, new Variable(null, $operand)),
                '$gt' => new GreaterThan($fieldVar, new Variable(null, $operand)),
                '$gte' => new GreaterThanOrEqualTo($fieldVar, new Variable(null, $operand)),
                '$lt' => new LessThan($fieldVar, new Variable(null, $operand)),
                '$lte' => new LessThanOrEqualTo($fieldVar, new Variable(null, $operand)),
                '$in' => new In($fieldVar, new Variable(null, $operand)),
                '$nin' => new NotIn($fieldVar, new Variable(null, $operand)),
                // Extended comparison operators (custom)
                '$same' => new SameAs($fieldVar, new Variable(null, $operand)),      // Strict ===
                '$nsame' => new NotSameAs($fieldVar, new Variable(null, $operand)),  // Strict !==
                '$between' => (static function () use ($fieldVar, $operand): Proposition {
                    throw_unless(is_array($operand), InvalidArgumentException::class, '$between requires array');

                    /** @var array<int, float|int> $operand */
                    return self::compileBetween($fieldVar, $operand);
                })(),
                // String operators
                '$regex' => self::compileRegex($fieldVar, is_string($operand) ? $operand : throw new InvalidArgumentException('$regex requires string'), is_string($value['$options'] ?? '') ? ($value['$options'] ?? '') : ''),
                '$notRegex' => new DoesNotMatch($fieldVar, new Variable(null, $operand)),
                '$startsWith' => new StartsWith($fieldVar, new Variable(null, $operand)),
                '$startsWithi' => new StartsWithInsensitive($fieldVar, new Variable(null, $operand)),
                '$endsWith' => new EndsWith($fieldVar, new Variable(null, $operand)),
                '$endsWithi' => new EndsWithInsensitive($fieldVar, new Variable(null, $operand)),
                '$contains' => new StringContains($fieldVar, new Variable(null, $operand)),
                '$containsi' => new StringContainsInsensitive($fieldVar, new Variable(null, $operand)),
                '$notContains' => new StringDoesNotContain($fieldVar, new Variable(null, $operand)),
                '$notContainsi' => new StringDoesNotContainInsensitive($fieldVar, new Variable(null, $operand)),
                '$strLength' => self::compileStringLength($fieldVar, $operand),
                // Date operators
                '$after' => new After($fieldVar, new Variable(null, $operand)),
                '$before' => new Before($fieldVar, new Variable(null, $operand)),
                '$betweenDates' => (static function () use ($fieldVar, $operand): Proposition {
                    throw_unless(is_array($operand), InvalidArgumentException::class, '$betweenDates requires array');

                    /** @var array<int, int|string> $operand */
                    return self::compileBetweenDates($fieldVar, $operand);
                })(),
                // Type operators
                '$exists' => self::compileExists($fieldVar, is_bool($operand) ? $operand : throw new InvalidArgumentException('$exists requires bool')),
                '$type' => self::compileType($fieldVar, is_string($operand) ? $operand : throw new InvalidArgumentException('$type requires string')),
                '$size' => self::compileArrayCount($fieldVar, $operand),
                '$empty' => self::compileEmpty($fieldVar, is_bool($operand) ? $operand : throw new InvalidArgumentException('$empty requires bool')),
                default => throw new InvalidArgumentException('Unsupported operator: '.$operator),
            };
        }

        // Multiple operators on same field = implicit AND
        return count($conditions) === 1 ? $conditions[0] : new LogicalAnd($conditions);
    }

    /**
     * Compile a $and logical operator.
     *
     * @param  array<int, array<string, mixed>> $conditions Array of query conditions to AND together
     * @return Proposition                      LogicalAnd proposition combining all conditions
     */
    private function compileAnd(array $conditions): Proposition
    {
        $props = array_map(fn (array $cond): Proposition => $this->compileQuery($cond), $conditions);

        return new LogicalAnd($props);
    }

    /**
     * Compile a $or logical operator.
     *
     * @param  array<int, array<string, mixed>> $conditions Array of query conditions to OR together
     * @return Proposition                      LogicalOr proposition combining all conditions
     */
    private function compileOr(array $conditions): Proposition
    {
        $props = array_map(fn (array $cond): Proposition => $this->compileQuery($cond), $conditions);

        return new LogicalOr($props);
    }

    /**
     * Compile a $nor logical operator.
     *
     * @param  array<int, array<string, mixed>> $conditions Array of query conditions to NOR together
     * @return Proposition                      LogicalNor proposition (none of the conditions can be true)
     */
    private function compileNor(array $conditions): Proposition
    {
        $props = array_map(fn (array $cond): Proposition => $this->compileQuery($cond), $conditions);

        return new LogicalNor($props);
    }

    /**
     * Compile a $not logical operator.
     *
     * @param  array<string, mixed>|string $condition Query condition to negate
     * @return Proposition                 LogicalNot proposition negating the condition
     */
    private function compileNot(array|string $condition): Proposition
    {
        // If string, treat as field name check
        if (is_string($condition)) {
            /** @var array<string, mixed> $arrayCondition */
            $arrayCondition = [$condition => ['$exists' => true]];
            $prop = $this->compileQuery($arrayCondition);
        } else {
            $prop = $this->compileQuery($condition);
        }

        return new LogicalNot([$prop]);
    }

    /**
     * Compile a $xor logical operator.
     *
     * @param  array<int, array<string, mixed>> $conditions Array of query conditions to XOR together
     * @return Proposition                      LogicalXor proposition (exactly one condition must be true)
     */
    private function compileXor(array $conditions): Proposition
    {
        $props = array_map(fn (array $cond): Proposition => $this->compileQuery($cond), $conditions);

        return new LogicalXor($props);
    }

    /**
     * Compile a $nand logical operator.
     *
     * @param  array<int, array<string, mixed>> $conditions Array of query conditions to NAND together
     * @return Proposition                      LogicalNand proposition (at least one condition must be false)
     */
    private function compileNand(array $conditions): Proposition
    {
        $props = array_map(fn (array $cond): Proposition => $this->compileQuery($cond), $conditions);

        return new LogicalNand($props);
    }
}
