<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\MongoDB;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use InvalidArgumentException;
use JsonException;

/**
 * Public facade for parsing MongoDB Query DSL expressions into Rules.
 *
 * Provides a clean interface for converting MongoDB-style query documents
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
 * $parser = new MongoQueryParser();
 * $rule = $parser->parse(['age' => ['$gte' => 18]]);
 * $result = $rule->evaluate($context);
 *
 * // Or from JSON string:
 * $rule = $parser->parseJson('{"age": {"$gte": 18}}');
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see MongoQueryRuleBuilder Internal implementation used by this facade
 * @see MongoQuerySerializer For converting Rules back to MongoDB query documents
 * @see MongoQueryValidator For validating MongoDB query documents
 *
 * @psalm-immutable
 */
final readonly class MongoQueryParser
{
    /**
     * Internal MongoDB query rule builder.
     */
    private MongoQueryRuleBuilder $builder;

    /**
     * Create a new MongoQueryParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = new MongoQueryRuleBuilder($ruleBuilder);
    }

    /**
     * Parse a MongoDB query document array into a Rule.
     *
     * Parses MongoDB-style query documents like {"age": {"$gte": 18}, "country": "US"}
     * into executable Rule objects. Supports all MongoDB operators including
     * comparison, logical, string, date, and type operations.
     *
     * @param array<string, mixed> $query The MongoDB query document to parse
     *
     * @throws InvalidArgumentException When query structure is invalid
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(array $query): Rule
    {
        return $this->builder->parse($query);
    }

    /**
     * Parse a JSON-encoded MongoDB query string into a Rule.
     *
     * Creates a Rule from a JSON string representation of a MongoDB query.
     * The JSON must decode to a valid query document object.
     *
     * @param string $json JSON-encoded MongoDB query document
     *
     * @throws JsonException If JSON string is malformed or cannot be decoded
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parseJson(string $json): Rule
    {
        return $this->builder->parseJson($json);
    }

    /**
     * Parse a MongoDB query array and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param array<string, mixed> $query  The MongoDB query document to parse
     * @param callable             $action Callback to execute when rule evaluates to true.
     *                                     Receives the context array as parameter.
     *
     * @throws InvalidArgumentException When query structure is invalid
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(array $query, callable $action): Rule
    {
        return $this->builder->parseWithAction($query, $action);
    }

    /**
     * Parse a JSON MongoDB query string and attach an action callback.
     *
     * Creates a Rule from JSON that executes the provided callback when the
     * parsed condition evaluates to true.
     *
     * @param string   $json   JSON-encoded MongoDB query document
     * @param callable $action Callback to execute when rule evaluates to true.
     *                         Receives the context array as parameter.
     *
     * @throws JsonException If JSON string is malformed or cannot be decoded
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseJsonWithAction(string $json, callable $action): Rule
    {
        return $this->builder->parseJsonWithAction($json, $action);
    }
}
