<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use LogicException;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Public facade for parsing Wirefilter DSL expressions into Rules.
 *
 * Provides a clean interface for converting text-based Wirefilter DSL expressions
 * into executable Rule objects. This class follows the standardized DSL facade
 * pattern that should be replicated across all DSL implementations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $parser = new WirefilterParser();
 * $rule = $parser->parse('age >= 18 && country in ["US", "CA"]');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see StringRuleBuilder Internal implementation used by this facade
 * @see WirefilterSerializer For converting Rules back to DSL strings
 * @see WirefilterValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class WirefilterParser
{
    /**
     * Internal DSL rule builder.
     */
    private StringRuleBuilder $builder;

    /**
     * Create a new WirefilterParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = new StringRuleBuilder($ruleBuilder);
    }

    /**
     * Parse a Wirefilter DSL expression string into a Rule.
     *
     * Parses Wirefilter-style DSL expressions like "age >= 18 && country == 'US'"
     * into executable Rule objects. Supports all Wirefilter operators including
     * comparison, logical, string, set, type, and mathematical operations.
     *
     * @param string $expression The Wirefilter DSL expression to parse
     *
     * @throws LogicException When compilation fails
     * @throws SyntaxError    When expression syntax is invalid
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        return $this->builder->parse($expression);
    }

    /**
     * Parse a Wirefilter DSL expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param string   $expression The Wirefilter DSL expression to parse
     * @param callable $action     Callback to execute when rule evaluates to true.
     *                             Receives the context array as parameter.
     *
     * @throws LogicException When compilation fails
     * @throws SyntaxError    When expression syntax is invalid
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(string $expression, callable $action): Rule
    {
        return $this->builder->parseWithAction($expression, $action);
    }
}
