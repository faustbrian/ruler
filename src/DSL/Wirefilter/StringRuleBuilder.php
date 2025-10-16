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
 * Facade for creating Rules from text-based DSL expressions.
 *
 * Provides a simple interface for parsing Wirefilter-style DSL expressions
 * into Ruler Rule objects. Handles the complete pipeline from text parsing
 * through compilation to Rule creation.
 *
 * Example usage:
 * ```php
 * $srb = new StringRuleBuilder;
 * $rule = $srb->parse('user.age >= 18 and user.country in ["US", "CA"]');
 * $result = $rule->evaluate($context);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class StringRuleBuilder
{
    /**
     * Expression parser for parsing DSL text into AST.
     */
    private ExpressionParser $parser;

    /**
     * AST compiler for converting parsed expressions to Propositions.
     */
    private RuleCompiler $compiler;

    /**
     * Create a new StringRuleBuilder instance.
     *
     * Initializes the parser and compiler with field resolver and operator
     * registry for complete DSL expression compilation.
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
        $operatorRegistry = new OperatorRegistry();

        $this->parser = new ExpressionParser();
        $this->compiler = new RuleCompiler($fieldResolver, $operatorRegistry);
    }

    /**
     * Parse a DSL expression string into a Rule.
     *
     * Parses Wirefilter-style DSL expressions like "user.age >= 18 and status eq 'active'"
     * into executable Rule objects. Supports all DSL operators including comparison,
     * logical, string, set, type, and mathematical operations.
     *
     * @param string $expression The DSL expression to parse
     *
     * @throws LogicException When compilation fails
     * @throws SyntaxError    When expression syntax is invalid
     *
     * @return Rule The compiled Rule ready for evaluation
     */
    public function parse(string $expression): Rule
    {
        $parsedExpression = $this->parser->parse($expression);
        $proposition = $this->compiler->compile($parsedExpression);

        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create($proposition);
    }

    /**
     * Parse a DSL expression and attach an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed
     * condition evaluates to true. The callback receives the evaluation
     * context as its argument.
     *
     * @param string   $expression The DSL expression to parse
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
        $parsedExpression = $this->parser->parse($expression);
        $proposition = $this->compiler->compile($parsedExpression);

        $rb = $this->ruleBuilder ?? new RuleBuilder();

        return $rb->create($proposition, $action);
    }
}
