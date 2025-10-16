<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use LogicException;
use RuntimeException;

/**
 * Public facade for parsing LDAP Filter DSL expressions into Rules.
 *
 * Provides a clean interface for converting LDAP filter syntax (RFC 4515)
 * into executable Rule objects. This class follows the standardized DSL facade
 * pattern that should be replicated across all DSL implementations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * LDAP Filter syntax uses prefix notation with the following operators:
 * - Comparison: =, >=, <=, ~= (approximate match)
 * - Presence: =* (attribute exists)
 * - Logical: & (AND), | (OR), ! (NOT)
 * - Format: (&(age>=18)(country=US))
 *
 * Example usage:
 * ```php
 * $parser = new LDAPFilterParser();
 * $rule = $parser->parse('(&(age>=18)(country=US))');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see LDAPFilterRuleBuilder Internal implementation used by this facade
 * @see LDAPFilterSerializer For converting Rules back to LDAP filter syntax
 * @see LDAPFilterValidator For validating LDAP filter strings
 *
 * @psalm-immutable
 */
final readonly class LDAPFilterParser
{
    /**
     * Internal LDAP rule builder.
     */
    private LDAPFilterRuleBuilder $builder;

    /**
     * Create a new LDAPFilterParser instance.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->builder = new LDAPFilterRuleBuilder($ruleBuilder);
    }

    /**
     * Parse an LDAP filter expression string into a Rule.
     *
     * Parses LDAP filter syntax following RFC 4515, supporting prefix notation
     * with logical operators (&, |, !) and comparison operators (=, >=, <=, ~=).
     * Also handles presence filters (=*) and extensible match filters.
     *
     * @param string $expression The LDAP filter expression to parse (e.g., '(&(age>=18)(country=US))')
     *
     * @throws LogicException   When compilation fails
     * @throws RuntimeException When filter syntax is invalid
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        return $this->builder->parse($expression);
    }

    /**
     * Parse an LDAP filter expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param string   $expression The LDAP filter expression to parse
     * @param callable $action     Callback to execute when rule evaluates to true.
     *                             Receives the context array as parameter.
     *
     * @throws LogicException   When compilation fails
     * @throws RuntimeException When filter syntax is invalid
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(string $expression, callable $action): Rule
    {
        return $this->builder->parseWithAction($expression, $action);
    }
}
