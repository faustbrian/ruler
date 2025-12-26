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
 * Builder for creating Rules from JMESPath expressions.
 *
 * Provides a facade for parsing JMESPath query expressions into executable
 * Rule objects. Handles expression validation and Rule creation with the
 * JMESPath evaluation engine.
 *
 * ```php
 * $builder = new JMESPathRuleBuilder();
 * $rule = $builder->parse('user.age >= `18` && user.country == `"US"`');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class JMESPathRuleBuilder
{
    /** @var JMESPathAdapter JMESPath adapter for expression evaluation */
    private JMESPathAdapter $adapter;

    /**
     * Create a new JMESPath rule builder.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder instance for creating Rules.
     *                                      If not provided, a new RuleBuilder will be instantiated
     *                                      when needed for rule creation and proposition wrapping.
     */
    public function __construct(
        private ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->adapter = new JMESPathAdapter();
    }

    /**
     * Parses a JMESPath expression into a Rule.
     *
     * Creates a JMESPathProposition from the expression and wraps it in a Rule
     * for evaluation against Context data.
     *
     * @param  string $expression JMESPath query expression to parse
     * @return Rule   The compiled Rule containing the JMESPath proposition
     */
    public function parse(string $expression): Rule
    {
        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create(
            new JMESPathProposition($expression, $this->adapter),
        );
    }

    /**
     * Parses a JMESPath expression into a Rule with an action callback.
     *
     * Creates a JMESPathProposition from the expression, wraps it in a Rule,
     * and attaches the provided action callback to execute when the rule
     * evaluates to true.
     *
     * @param  string   $expression JMESPath query expression to parse
     * @param  callable $action     Callback to execute when rule evaluates to true
     * @return Rule     The compiled Rule with attached action callback
     */
    public function parseWithAction(string $expression, callable $action): Rule
    {
        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create(
            new JMESPathProposition($expression, $this->adapter),
            $action,
        );
    }

    /**
     * Validates JMESPath expression syntax.
     *
     * Attempts to evaluate the expression with empty data to check for syntax
     * errors without requiring actual runtime data.
     *
     * @param  string $expression JMESPath expression to validate
     * @return bool   True if expression is syntactically valid, false otherwise
     */
    public function validate(string $expression): bool
    {
        try {
            $this->adapter->evaluate($expression, []);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
