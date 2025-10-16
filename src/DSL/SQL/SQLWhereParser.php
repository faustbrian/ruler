<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SQL;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use InvalidArgumentException;

/**
 * Public facade for parsing SQL WHERE clause expressions into Rules.
 *
 * Provides a clean interface for converting SQL WHERE clause syntax into
 * executable Rule objects. This class follows the standardized DSL facade
 * pattern that should be replicated across all DSL implementations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $parser = new SQLWhereParser();
 * $rule = $parser->parse("age >= 18 AND country = 'US'");
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see SqlWhereRuleBuilder Internal implementation used by this facade
 * @see SQLWhereSerializer For converting Rules back to SQL WHERE strings
 * @see SQLWhereValidator For validating SQL WHERE strings
 *
 * @psalm-immutable
 */
final readonly class SQLWhereParser
{
    /**
     * Internal DSL rule builder.
     */
    private SqlWhereRuleBuilder $builder;

    /**
     * Create a new SQLWhereParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = new SqlWhereRuleBuilder($ruleBuilder);
    }

    /**
     * Parse a SQL WHERE clause expression into a Rule.
     *
     * Parses SQL WHERE clause syntax like "age >= 18 AND country = 'US'"
     * into executable Rule objects. Does not require the WHERE keyword prefix.
     * Supports all SQL operators including comparison, logical, set, and null checks.
     *
     * @param string $expression The SQL WHERE clause expression to parse (without 'WHERE' keyword)
     *
     * @throws InvalidArgumentException When SQL syntax is invalid
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        return $this->builder->parse($expression);
    }

    /**
     * Parse a SQL WHERE clause expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param string   $expression The SQL WHERE clause expression to parse
     * @param callable $action     Callback to execute when rule evaluates to true.
     *                             Receives the context array as parameter.
     *
     * @throws InvalidArgumentException When SQL syntax is invalid
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(string $expression, callable $action): Rule
    {
        return $this->builder->parseWithAction($expression, $action);
    }
}
