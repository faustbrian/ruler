<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

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
use Cline\Ruler\Operators\Mathematical\Abs;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Operators\Mathematical\Ceil;
use Cline\Ruler\Operators\Mathematical\Division;
use Cline\Ruler\Operators\Mathematical\Exponentiate;
use Cline\Ruler\Operators\Mathematical\Floor;
use Cline\Ruler\Operators\Mathematical\Max;
use Cline\Ruler\Operators\Mathematical\Min;
use Cline\Ruler\Operators\Mathematical\Modulo;
use Cline\Ruler\Operators\Mathematical\Multiplication;
use Cline\Ruler\Operators\Mathematical\Negation;
use Cline\Ruler\Operators\Mathematical\Round;
use Cline\Ruler\Operators\Mathematical\Subtraction;
use Cline\Ruler\Operators\Set\Complement;
use Cline\Ruler\Operators\Set\ContainsSubset;
use Cline\Ruler\Operators\Set\DoesNotContainSubset;
use Cline\Ruler\Operators\Set\Intersect;
use Cline\Ruler\Operators\Set\SetContains;
use Cline\Ruler\Operators\Set\SetDoesNotContain;
use Cline\Ruler\Operators\Set\SymmetricDifference;
use Cline\Ruler\Operators\Set\Union;
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
use LogicException;

use function array_key_exists;
use function array_keys;
use function sprintf;
use function throw_unless;

/**
 * Registry mapping DSL operator names to Operator class names.
 *
 * Provides centralized mapping from text-based DSL operators (e.g., "eq", "contains")
 * to their corresponding Ruler Operator classes. Used by the RuleCompiler to translate
 * parsed expressions into Operator trees.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class OperatorRegistry
{
    /**
     * Map of DSL operator names to fully-qualified Operator class names.
     *
     * @var array<string, class-string>
     */
    private const array OPERATORS = [
        // Comparison operators (11)
        'eq' => EqualTo::class,
        'ne' => NotEqualTo::class,
        'gt' => GreaterThan::class,
        'gte' => GreaterThanOrEqualTo::class,
        'lt' => LessThan::class,
        'lte' => LessThanOrEqualTo::class,
        'is' => SameAs::class,
        'isNot' => NotSameAs::class,
        'in' => In::class,
        'notIn' => NotIn::class,
        'between' => Between::class,
        // Logical operators (6)
        'and' => LogicalAnd::class,
        'or' => LogicalOr::class,
        'not' => LogicalNot::class,
        'xor' => LogicalXor::class,
        'nand' => LogicalNand::class,
        'nor' => LogicalNor::class,
        // Mathematical operators (13)
        'add' => Addition::class,
        'subtract' => Subtraction::class,
        'multiply' => Multiplication::class,
        'divide' => Division::class,
        'modulo' => Modulo::class,
        'exponentiate' => Exponentiate::class,
        'negate' => Negation::class,
        'abs' => Abs::class,
        'ceil' => Ceil::class,
        'floor' => Floor::class,
        'round' => Round::class,
        'min' => Min::class,
        'max' => Max::class,
        // String operators (10)
        'contains' => StringContains::class,
        'doesNotContain' => StringDoesNotContain::class,
        'icontains' => StringContainsInsensitive::class,
        'doesNotContainInsensitive' => StringDoesNotContainInsensitive::class,
        'startsWith' => StartsWith::class,
        'istartsWith' => StartsWithInsensitive::class,
        'endsWith' => EndsWith::class,
        'iendsWith' => EndsWithInsensitive::class,
        'matches' => Matches::class,
        'doesNotMatch' => DoesNotMatch::class,
        'stringLength' => StringLength::class,
        // Set operators (8)
        'union' => Union::class,
        'intersect' => Intersect::class,
        'complement' => Complement::class,
        'symmetricDifference' => SymmetricDifference::class,
        'containsSubset' => ContainsSubset::class,
        'doesNotContainSubset' => DoesNotContainSubset::class,
        'setContains' => SetContains::class,
        'setDoesNotContain' => SetDoesNotContain::class,
        // Type operators (7)
        'isArray' => IsArray::class,
        'isBoolean' => IsBoolean::class,
        'isEmpty' => IsEmpty::class,
        'isNull' => IsNull::class,
        'isNumeric' => IsNumeric::class,
        'isString' => IsString::class,
        'arrayCount' => ArrayCount::class,
        // Date operators (3)
        'after' => After::class,
        'before' => Before::class,
        'isBetweenDates' => IsBetweenDates::class,
    ];

    /**
     * Get the Operator class for a given DSL operator name.
     *
     * Looks up the fully-qualified Operator class name for a DSL operator.
     * This mapping is used by the RuleCompiler to instantiate the correct
     * Operator class when compiling parsed expressions.
     *
     * @param string $operatorName The DSL operator name (e.g., "eq", "contains", "gt")
     *
     * @throws LogicException When the operator name is not registered in the mapping
     *
     * @return class-string The fully-qualified Operator class name
     */
    public function get(string $operatorName): string
    {
        throw_unless(array_key_exists($operatorName, self::OPERATORS), LogicException::class, sprintf('Unknown DSL operator: "%s"', $operatorName));

        return self::OPERATORS[$operatorName];
    }

    /**
     * Check if an operator is registered.
     *
     * Verifies whether a given DSL operator name exists in the registry
     * without throwing an exception.
     *
     * @param  string $operatorName The DSL operator name to check
     * @return bool   True if the operator is registered, false otherwise
     */
    public function has(string $operatorName): bool
    {
        return array_key_exists($operatorName, self::OPERATORS);
    }

    /**
     * Get all registered operator names.
     *
     * Returns a complete list of supported DSL operators. Useful for
     * documentation, validation, or building autocomplete functionality.
     *
     * @return array<int, string> Array of all registered DSL operator names
     */
    public function all(): array
    {
        return array_keys(self::OPERATORS);
    }
}
