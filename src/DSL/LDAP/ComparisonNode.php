<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

/**
 * Represents a comparison operator node in LDAP filter AST.
 *
 * Handles standard comparison operators (=, >=, <=, >, <, !=) in LDAP filter
 * expressions. Compiled to corresponding Ruler comparison operators.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ComparisonNode extends LDAPNode
{
    /**
     * Create a new comparison node.
     *
     * @param string $operator  comparison operator symbol from the LDAP filter: '=' for equality,
     *                          '>=' for greater-than-or-equal, '<=' for less-than-or-equal,
     *                          '>' for greater-than, '<' for less-than, or '!=' for not-equal
     * @param string $attribute LDAP attribute name or field path to compare. Supports dot-notation
     *                          for nested field access when resolved during compilation.
     * @param string $value     String value to compare against the attribute. Automatically coerced
     *                          to appropriate PHP type (bool, null, int, float, string) during
     *                          compilation based on the value's format.
     */
    public function __construct(
        public readonly string $operator,
        public readonly string $attribute,
        public readonly string $value,
    ) {}
}
