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
use Cline\Ruler\DSL\Wirefilter\FieldResolver;

/**
 * Builds executable rules from GraphQL filter syntax.
 *
 * GraphQLFilterRuleBuilder provides a high-level interface for parsing GraphQL-style
 * filter queries and compiling them into Rule instances that can be evaluated against
 * data contexts. It orchestrates the parsing, compilation, and rule building pipeline,
 * handling both JSON string and PHP array input formats.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class GraphQLFilterRuleBuilder
{
    /** @var GraphQLParser parser that transforms GraphQL filter syntax into AST nodes */
    private GraphQLParser $parser;

    /** @var GraphQLCompiler compiler that converts AST nodes into proposition instances */
    private GraphQLCompiler $compiler;

    /**
     * Create a new GraphQL filter rule builder.
     *
     * Initializes the parsing and compilation pipeline with a parser for GraphQL
     * filter syntax, a compiler for AST-to-proposition transformation, and a rule
     * builder for final rule construction. The field resolver enables dot notation
     * access to nested data structures during evaluation.
     *
     * @param RuleBuilder $ruleBuilder Optional custom rule builder instance. If not provided,
     *                                 a default RuleBuilder is created for standard
     *                                 rule construction and evaluation behavior.
     */
    public function __construct(
        private RuleBuilder $ruleBuilder = new RuleBuilder(),
    ) {
        $this->parser = new GraphQLParser();

        $fieldResolver = new FieldResolver($this->ruleBuilder);
        $this->compiler = new GraphQLCompiler($fieldResolver);
    }

    /**
     * Parse GraphQL filter syntax and build an executable rule.
     *
     * Accepts GraphQL filter queries in JSON string or PHP array format, parses
     * the syntax into an abstract syntax tree, compiles the AST into propositions,
     * and wraps the result in an executable Rule instance ready for evaluation.
     *
     * @param  array<string, mixed>|string $filter GraphQL filter query as JSON string or PHP array.
     *                                             Supports logical operators (AND/OR/NOT), comparison
     *                                             operators (eq/ne/gt/gte/lt/lte), list operators
     *                                             (in/notIn), string operators (contains/startsWith/
     *                                             endsWith/match), null checks, and type validations.
     * @return Rule                        compiled rule ready for evaluation against data contexts using the evaluate() method
     */
    public function parse(string|array $filter): Rule
    {
        $ast = $this->parser->parse($filter);
        $proposition = $this->compiler->compile($ast);

        return $this->ruleBuilder->create($proposition);
    }

    /**
     * Parse JSON filter string and build an executable rule.
     *
     * Convenience method that provides a more intuitive name when working with
     * JSON input. Internally delegates to parse() for identical behavior.
     *
     * @param  string $json graphQL filter query as JSON string conforming to GraphQL filter syntax
     * @return Rule   compiled rule ready for evaluation against data contexts
     */
    public function parseJson(string $json): Rule
    {
        return $this->parse($json);
    }

    /**
     * Parse GraphQL filter syntax and build an executable rule with an action callback.
     *
     * Creates a Rule that executes the provided callback when the parsed condition
     * evaluates to true. The callback receives the evaluation context as its argument.
     *
     * @param  array<string, mixed>|string $filter GraphQL filter query as JSON string or PHP array
     * @param  callable                    $action Callback to execute when rule evaluates to true.
     *                                             Receives the context array as parameter.
     * @return Rule                        compiled rule with attached action callback
     */
    public function parseWithAction(string|array $filter, callable $action): Rule
    {
        $ast = $this->parser->parse($filter);
        $proposition = $this->compiler->compile($ast);

        return $this->ruleBuilder->create($proposition, $action);
    }
}
