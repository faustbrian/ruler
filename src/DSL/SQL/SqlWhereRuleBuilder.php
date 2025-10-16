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
use Cline\Ruler\DSL\Wirefilter\FieldResolver;
use Exception;
use InvalidArgumentException;

/**
 * Facade for creating Rules from SQL WHERE clause expressions.
 *
 * Provides a simple interface for parsing SQL WHERE clause syntax into
 * Ruler Rule objects. Handles the complete pipeline from SQL parsing
 * through compilation to Rule creation.
 *
 * Example usage:
 * ```php
 * $srb = new SqlWhereRuleBuilder();
 * $rule = $srb->parse("age >= 18 AND country = 'US'");
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class SqlWhereRuleBuilder
{
    /**
     * SQL parser for tokenizing and parsing WHERE clauses.
     */
    private SqlParser $parser;

    /**
     * AST compiler for converting parsed SQL nodes to Propositions.
     */
    private SqlCompiler $compiler;

    /**
     * Create a new SqlWhereRuleBuilder instance.
     *
     * Initializes the parser and compiler with appropriate field resolver
     * for variable creation during compilation.
     *
     * @param null|RuleBuilder $ruleBuilder Optional RuleBuilder for creating Variables and Rules.
     *                                      If not provided, a new instance will be created
     *                                      for each parse operation.
     */
    public function __construct(
        private ?RuleBuilder $ruleBuilder = null,
    ) {
        $rb = $this->ruleBuilder ?? new RuleBuilder();
        $fieldResolver = new FieldResolver($rb);

        $this->parser = new SqlParser();
        $this->compiler = new SqlCompiler($fieldResolver);
    }

    /**
     * Parse a SQL WHERE clause expression into a Rule.
     *
     * Parses SQL WHERE syntax like "age >= 18 AND country = 'US'" into
     * executable Rule objects. Does not require the WHERE keyword prefix.
     *
     * @param string $sql The SQL WHERE clause expression to parse (without 'WHERE' keyword)
     *
     * @throws InvalidArgumentException When SQL syntax is invalid
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $sql): Rule
    {
        $ast = $this->parser->parse($sql);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create($proposition);
    }

    /**
     * Parse a SQL WHERE clause expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. Useful for defining rule-based workflows.
     *
     * @param string   $sql    The SQL WHERE clause expression to parse
     * @param callable $action Callback to execute when rule evaluates to true
     *
     * @throws InvalidArgumentException When SQL syntax is invalid
     *
     * @return Rule The compiled Rule with attached action callback
     */
    public function parseWithAction(string $sql, callable $action): Rule
    {
        $ast = $this->parser->parse($sql);
        $proposition = $this->compiler->compile($ast);

        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create($proposition, $action);
    }

    /**
     * Validate SQL syntax without creating Rule.
     *
     * Performs syntax validation by attempting to parse the expression
     * without compiling or creating a Rule instance. Returns false for
     * any parsing errors instead of throwing exceptions.
     *
     * @param  string $sql The SQL WHERE clause expression to validate
     * @return bool   True if SQL syntax is valid, false otherwise
     */
    public function validate(string $sql): bool
    {
        try {
            $this->parser->parse($sql);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
