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
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
use Exception;

/**
 * Facade for creating Rules from LDAP filter expressions.
 *
 * Provides a simple interface for parsing LDAP filter syntax (RFC 4515)
 * into Ruler Rule objects. Handles the complete pipeline from LDAP parsing
 * through compilation to Rule creation.
 *
 * Example usage:
 * ```php
 * $ldap = new LDAPFilterRuleBuilder;
 * $rule = $ldap->parse('(&(age>=18)(country=US))');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LDAPFilterRuleBuilder
{
    private LDAPParser $parser;

    private LDAPCompiler $compiler;

    /**
     * Create a new LDAP filter rule builder.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder instance for creating Rules.
     *                                      If not provided, a new RuleBuilder will be instantiated
     *                                      when needed. Sharing a RuleBuilder across multiple parsers
     *                                      allows for consistent variable resolution and rule creation.
     */
    public function __construct(
        private ?RuleBuilder $ruleBuilder = null,
    ) {
        $rb = $this->ruleBuilder ?? new RuleBuilder();
        $fieldResolver = new FieldResolver($rb);
        $operatorRegistry = new LDAPOperatorRegistry();

        $this->parser = new LDAPParser();
        $this->compiler = new LDAPCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parses an LDAP filter string into a Rule.
     *
     * Tokenizes, parses, and compiles the LDAP filter expression through the
     * complete pipeline to produce an executable Rule.
     *
     * @param  string $filter LDAP filter expression following RFC 4515 syntax
     * @return Rule   The compiled Rule ready for evaluation
     */
    public function parse(string $filter): Rule
    {
        $ast = $this->parser->parse($filter);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create($proposition);
    }

    /**
     * Parses an LDAP filter and attaches an action callback.
     *
     * Creates a Rule with an associated action that will be executed when the
     * Rule evaluates to true.
     *
     * @param  string   $filter LDAP filter expression following RFC 4515 syntax
     * @param  callable $action Callback to execute when the rule evaluates to true.
     *                          Receives the Context as its parameter.
     * @return Rule     The compiled Rule with attached action
     */
    public function parseWithAction(string $filter, callable $action): Rule
    {
        $ast = $this->parser->parse($filter);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create($proposition, $action);
    }

    /**
     * Validates LDAP filter syntax.
     *
     * Attempts to parse the filter expression to check for syntax errors without
     * compiling or executing it.
     *
     * @param  string $filter LDAP filter expression to validate
     * @return bool   True if the filter is syntactically valid, false otherwise
     */
    public function validate(string $filter): bool
    {
        try {
            $this->parser->parse($filter);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
