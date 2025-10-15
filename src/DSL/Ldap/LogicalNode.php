<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Ldap;

/**
 * Represents a logical operator node in LDAP filter AST.
 *
 * Handles AND (&), OR (|), and NOT (!) operators that combine multiple
 * conditions in LDAP filter expressions. This node type forms the backbone
 * of complex filter logic by enabling compound boolean expressions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LogicalNode extends LdapNode
{
    /**
     * Create a new logical operator node.
     *
     * @param string               $operator   Logical operator type that determines how conditions
     *                                         are combined. Valid values are 'and' (maps to LDAP &),
     *                                         'or' (maps to LDAP |), or 'not' (maps to LDAP !).
     *                                         Controls the boolean logic applied to child conditions.
     * @param array<int, LdapNode> $conditions Child condition nodes that will be combined using the
     *                                         specified logical operator. For 'and' and 'or', typically
     *                                         contains 2+ nodes. For 'not', typically contains 1 node
     *                                         to negate. Each node represents a sub-expression in the filter.
     */
    public function __construct(
        public readonly string $operator,
        public readonly array $conditions,
    ) {}
}
