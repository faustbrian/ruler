<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use InvalidArgumentException;

/**
 * Public facade for parsing Natural Language DSL expressions into Rules.
 *
 * Provides a clean interface for converting human-readable natural language
 * expressions into executable Rule objects. This class follows the standardized
 * DSL facade pattern that should be replicated across all DSL implementations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $parser = new NaturalLanguageParser();
 * $rule = $parser->parse('age is greater than or equal to 18 and country equals US');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see NaturalLanguageRuleBuilder Internal implementation used by this facade
 * @see NaturalLanguageSerializer For converting Rules back to DSL strings
 * @see NaturalLanguageValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class NaturalLanguageParser
{
    /**
     * Internal DSL rule builder.
     */
    private NaturalLanguageRuleBuilder $builder;

    /**
     * Create a new NaturalLanguageParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = new NaturalLanguageRuleBuilder($ruleBuilder);
    }

    /**
     * Parse a Natural Language DSL expression string into a Rule.
     *
     * Parses natural language expressions like "age is greater than or equal to 18 and
     * country equals US" into executable Rule objects. Supports comparison operators
     * (is, is greater than, is less than, etc.), logical operators (and, or), range
     * checks (is between), list membership (is one of), and string operations
     * (contains, starts with, ends with).
     *
     * @param string $expression The Natural Language DSL expression to parse
     *
     * @throws InvalidArgumentException When expression cannot be parsed
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        return $this->builder->parse($expression);
    }

    /**
     * Parse a Natural Language DSL expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param string   $expression The Natural Language DSL expression to parse
     * @param callable $action     Callback to execute when rule evaluates to true.
     *                             Receives the context array as parameter.
     *
     * @throws InvalidArgumentException When expression cannot be parsed
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(string $expression, callable $action): Rule
    {
        return $this->builder->parseWithAction($expression, $action);
    }
}
