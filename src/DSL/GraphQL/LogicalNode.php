<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Represents a logical combinator operation in GraphQL filter queries.
 *
 * LogicalNode encapsulates logical operations (AND/OR/NOT) that combine multiple
 * filter conditions into compound boolean expressions. These nodes form the structural
 * backbone of complex filter queries, enabling recursive composition of arbitrarily
 * complex logical expressions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNode extends GraphQLNode
{
    /**
     * Create a new logical combinator node.
     *
     * @param string                  $operator   The logical operation to perform. Supported values: "and"
     *                                            combines conditions with conjunction (all must be true), "or"
     *                                            combines with disjunction (at least one must be true), "not"
     *                                            negates a single condition. The operator determines which
     *                                            logical operator class is instantiated during compilation.
     * @param array<int, GraphQLNode> $conditions Array of child condition nodes to combine with the logical
     *                                            operator. For AND and OR operations, can contain multiple
     *                                            conditions. For NOT operations, should contain exactly one
     *                                            condition to negate. Supports arbitrary nesting depth for
     *                                            complex filter logic composition.
     */
    public function __construct(
        public readonly string $operator,
        public readonly array $conditions,
    ) {}
}
