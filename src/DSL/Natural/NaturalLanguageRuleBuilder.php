<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Natural;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Rule;
use InvalidArgumentException;

/**
 * Builds executable rules from natural language expressions.
 *
 * Provides a high-level interface for converting human-readable rule
 * expressions into executable Rule objects. Orchestrates the complete
 * pipeline from text parsing through AST compilation to rule instantiation,
 * making rules accessible to non-technical users.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class NaturalLanguageRuleBuilder
{
    /**
     * Parser for converting text to AST.
     */
    private ASTParser $parser;

    /**
     * Compiler for converting AST to propositions.
     */
    private NaturalLanguageCompiler $compiler;

    /**
     * Rule builder instance for managing variable context.
     */
    private RuleBuilder $ruleBuilder;

    /**
     * Create a new natural language rule builder.
     *
     * @param null|RuleBuilder $ruleBuilder Optional rule builder instance. If null, creates a new
     *                                      default RuleBuilder for managing variable context and
     *                                      rule creation throughout the parsing and compilation pipeline.
     */
    public function __construct(
        ?RuleBuilder $ruleBuilder = null,
    ) {
        $this->ruleBuilder = $ruleBuilder ?? new RuleBuilder();
        $this->parser = new ASTParser();
        $this->compiler = new NaturalLanguageCompiler($this->ruleBuilder);
    }

    /**
     * Parse a natural language expression and return an executable Rule.
     *
     * Converts a human-readable rule expression like "age is greater than 18 and
     * status equals active" into an executable Rule object through the complete
     * parse-compile pipeline.
     *
     * @param string $text Natural language rule expression to parse
     *
     * @throws InvalidArgumentException If expression cannot be parsed
     *
     * @return Rule Compiled rule ready for evaluation against data
     */
    public function parse(string $text): Rule
    {
        $ast = $this->parser->parse($text);
        $proposition = $this->compiler->compile($ast);

        return $this->ruleBuilder->create($proposition);
    }

    /**
     * Parse a natural language expression with an action callback.
     *
     * Converts a human-readable rule expression into an executable Rule object
     * with an attached action that executes when the rule evaluates to true.
     *
     * @param string   $text   Natural language rule expression to parse
     * @param callable $action Callback to execute when rule evaluates to true
     *
     * @throws InvalidArgumentException If expression cannot be parsed
     *
     * @return Rule Compiled rule with action callback
     */
    public function parseWithAction(string $text, callable $action): Rule
    {
        $ast = $this->parser->parse($text);
        $proposition = $this->compiler->compile($ast);

        return $this->ruleBuilder->create($proposition, $action);
    }
}
