<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use function implode;
use function in_array;
use function preg_match_all;
use function sprintf;

/**
 * Parses DSL expression strings using symfony/expression-language.
 *
 * Configures ExpressionLanguage with custom operators and precedence rules
 * to support the Wirefilter-style DSL syntax. The parser converts text
 * expressions into ParsedExpression AST nodes that can be compiled by RuleCompiler.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ExpressionParser
{
    /**
     * Symfony ExpressionLanguage instance configured with custom operators.
     */
    private ExpressionLanguage $expressionLanguage;

    /**
     * Create a new ExpressionParser instance.
     *
     * Initializes the ExpressionLanguage and registers all custom DSL
     * operators as functions for proper parsing.
     */
    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->registerCustomFunctions();
    }

    /**
     * Parse a DSL expression string into a ParsedExpression AST.
     *
     * Extracts variable names from the expression and passes them to
     * ExpressionLanguage for proper parsing and validation.
     *
     * @param  string           $expression The DSL expression to parse (e.g., "user.age >= 18 and status eq 'active'")
     * @return ParsedExpression The parsed expression as an Abstract Syntax Tree
     */
    public function parse(string $expression): ParsedExpression
    {
        $variables = self::extractVariableNames($expression);

        return $this->expressionLanguage->parse($expression, $variables);
    }

    /**
     * Extract variable names from an expression.
     *
     * Uses regex to find potential variable names while excluding reserved
     * words. Variable names must start with a letter or underscore and can
     * contain letters, numbers, and underscores. Reserved words include
     * operators, logical connectives, and built-in functions.
     *
     * @param  string             $expression The expression to analyze for variables
     * @return array<int, string> Array of unique variable names with numeric keys
     *                            required by symfony/expression-language
     */
    private static function extractVariableNames(string $expression): array
    {
        // Match variable names (word characters at start or after whitespace/operators)
        // This regex captures: word boundaries followed by identifiers
        preg_match_all('/\b([a-zA-Z_]\w*)\b/', $expression, $matches);

        $variables = [];
        $reservedWords = [
            'and', 'or', 'not', 'xor', 'nand', 'nor', 'true', 'false', 'null',
            'eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'is', 'isNot',
            'in', 'notIn', 'between',
            'contains', 'doesNotContain', 'icontains', 'doesNotContainInsensitive',
            'startsWith', 'istartsWith', 'endsWith', 'iendsWith',
            'matches', 'doesNotMatch', 'stringLength',
            'union', 'intersect', 'complement', 'symmetricDifference',
            'containsSubset', 'doesNotContainSubset', 'setContains', 'setDoesNotContain',
            'isArray', 'isBoolean', 'isEmpty', 'isNull', 'isNumeric', 'isString', 'arrayCount',
            'after', 'before', 'isBetweenDates',
            'abs', 'ceil', 'floor', 'round', 'min', 'max', 'negate', 'modulo', 'exponentiate',
        ];

        foreach ($matches[1] as $match) {
            if (!in_array($match, $reservedWords, true) && !in_array($match, $variables, true)) {
                $variables[] = $match;
            }
        }

        return $variables;
    }

    /**
     * Register custom functions for DSL operators.
     *
     * Registers all Wirefilter DSL operators as ExpressionLanguage functions.
     * This includes comparison operators (eq, ne, gt, etc.), string operators
     * (contains, startsWith, etc.), set operators, type checks, and date operators.
     * The functions are registered as pass-through placeholders for later compilation.
     */
    private function registerCustomFunctions(): void
    {
        // Comparison operators that don't have PHP equivalents
        $this->registerFunction('eq');
        $this->registerFunction('ne');
        $this->registerFunction('gt');
        $this->registerFunction('gte');
        $this->registerFunction('lt');
        $this->registerFunction('lte');
        $this->registerFunction('is');
        $this->registerFunction('isNot');
        $this->registerFunction('in');
        $this->registerFunction('notIn');
        $this->registerFunction('between');

        // Logical operators (and, or, not are built into ExpressionLanguage)
        $this->registerFunction('xor');
        $this->registerFunction('nand');
        $this->registerFunction('nor');

        // String operators
        $this->registerFunction('contains');
        $this->registerFunction('doesNotContain');
        $this->registerFunction('icontains');
        $this->registerFunction('doesNotContainInsensitive');
        $this->registerFunction('startsWith');
        $this->registerFunction('istartsWith');
        $this->registerFunction('endsWith');
        $this->registerFunction('iendsWith');
        $this->registerFunction('matches');
        $this->registerFunction('doesNotMatch');
        $this->registerFunction('stringLength');

        // Set operators
        $this->registerFunction('union');
        $this->registerFunction('intersect');
        $this->registerFunction('complement');
        $this->registerFunction('symmetricDifference');
        $this->registerFunction('containsSubset');
        $this->registerFunction('doesNotContainSubset');
        $this->registerFunction('setContains');
        $this->registerFunction('setDoesNotContain');

        // Type operators
        $this->registerFunction('isArray');
        $this->registerFunction('isBoolean');
        $this->registerFunction('isEmpty');
        $this->registerFunction('isNull');
        $this->registerFunction('isNumeric');
        $this->registerFunction('isString');
        $this->registerFunction('arrayCount');

        // Date operators
        $this->registerFunction('after');
        $this->registerFunction('before');
        $this->registerFunction('isBetweenDates');

        // Mathematical functions (most are built into ExpressionLanguage via PHP operators)
        $this->registerFunction('abs');
        $this->registerFunction('ceil');
        $this->registerFunction('floor');
        $this->registerFunction('round');
        $this->registerFunction('min');
        $this->registerFunction('max');
        $this->registerFunction('negate');
        $this->registerFunction('modulo');
        $this->registerFunction('exponentiate');
    }

    /**
     * Register a custom function with the expression language.
     *
     * Creates a pass-through function that preserves the function call
     * in the AST for later compilation by RuleCompiler. The compiler
     * will map these function calls to actual Ruler Operator instances.
     *
     * @param string $name The function name to register (e.g., "eq", "contains")
     */
    private function registerFunction(string $name): void
    {
        $this->expressionLanguage->register(
            $name,
            function (...$args) use ($name): string {
                /** @var array<int, mixed> $args */
                return sprintf('%s(%s)', $name, implode(', ', $args)); // @codeCoverageIgnore
            },
            fn ($arguments, ...$args): array => [$name => $args], // @codeCoverageIgnore
        );
    }
}
