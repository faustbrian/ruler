<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\JMESPath;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use Exception;

/**
 * Public facade for parsing JMESPath filter expressions into Rules.
 *
 * Provides a clean interface for converting JMESPath filter expressions
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
 * $parser = new JMESPathParser();
 * $rule = $parser->parse('user.age >= `18` && user.country == `"US"`');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see JMESPathRuleBuilder Internal implementation used by this facade
 * @see JMESPathSerializer For converting Rules back to DSL strings
 * @see JMESPathValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class JMESPathParser
{
    /**
     * Internal DSL rule builder.
     */
    private JMESPathRuleBuilder $builder;

    /**
     * Create a new JMESPathParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = new JMESPathRuleBuilder($ruleBuilder);
    }

    /**
     * Parse a JMESPath filter expression string into a Rule.
     *
     * Parses JMESPath-style filter expressions like "user.age >= `18`"
     * into executable Rule objects. Supports JMESPath comparison operators,
     * functions, and filter syntax.
     *
     * @param string $expression The JMESPath filter expression to parse
     *
     * @throws Exception When expression evaluation fails
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        return $this->builder->parse($expression);
    }

    /**
     * Parse a JMESPath filter expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param string   $expression The JMESPath filter expression to parse
     * @param callable $action     Callback to execute when rule evaluates to true.
     *                             Receives the context array as parameter.
     *
     * @throws Exception When expression evaluation fails
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(string $expression, callable $action): Rule
    {
        return $this->builder->parseWithAction($expression, $action);
    }
}
