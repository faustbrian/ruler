<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use InvalidArgumentException;
use JsonException;

/**
 * Public facade for parsing GraphQL Filter DSL expressions into Rules.
 *
 * Provides a clean interface for converting GraphQL-style filter objects
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
 * $parser = new GraphQLFilterParser();
 * $rule = $parser->parse(['age' => ['gte' => 18], 'country' => ['in' => ['US', 'CA']]]);
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see GraphQLFilterRuleBuilder Internal implementation used by this facade
 * @see GraphQLFilterSerializer For converting Rules back to DSL strings
 * @see GraphQLFilterValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class GraphQLFilterParser
{
    /**
     * Internal DSL rule builder.
     */
    private GraphQLFilterRuleBuilder $builder;

    /**
     * Create a new GraphQLFilterParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = $ruleBuilder instanceof RuleBuilder
            ? new GraphQLFilterRuleBuilder($ruleBuilder)
            : new GraphQLFilterRuleBuilder();
    }

    /**
     * Parse a GraphQL Filter DSL expression into a Rule.
     *
     * Parses GraphQL-style filter expressions like ['age' => ['gte' => 18], 'country' => 'US']
     * into executable Rule objects. Supports all GraphQL filter operators including
     * comparison (eq, ne, gt, gte, lt, lte), logical (AND, OR, NOT), string (contains,
     * startsWith, endsWith, match), and set operations (in, notIn).
     *
     * @param array<string, mixed>|string $filter The GraphQL filter expression as array or JSON string
     *
     * @throws InvalidArgumentException When filter format is invalid
     * @throws JsonException            When JSON parsing fails
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(array|string $filter): Rule
    {
        return $this->builder->parse($filter);
    }

    /**
     * Parse a GraphQL Filter DSL expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param array<string, mixed>|string $filter The GraphQL filter expression as array or JSON string
     * @param callable                    $action Callback to execute when rule evaluates to true.
     *                                            Receives the context array as parameter.
     *
     * @throws InvalidArgumentException When filter format is invalid
     * @throws JsonException            When JSON parsing fails
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(array|string $filter, callable $action): Rule
    {
        return $this->builder->parseWithAction($filter, $action);
    }
}
