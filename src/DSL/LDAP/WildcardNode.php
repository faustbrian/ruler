<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

/**
 * Represents a wildcard pattern node in LDAP filter AST.
 *
 * Handles attribute matching with asterisk wildcards for flexible pattern matching.
 * Supports various wildcard patterns including prefix matching (value*), suffix
 * matching (*value), substring matching (*value*), and complex multi-wildcard
 * patterns (a*b*c). Maps to LDAP's substring filter assertions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class WildcardNode extends LDAPNode
{
    /**
     * Create a new wildcard pattern node.
     *
     * @param string $attribute Field or attribute name to apply the wildcard pattern against
     *                          when matching directory entries. Used as the left-hand side
     *                          of the LDAP substring filter assertion.
     * @param string $pattern   Wildcard pattern containing one or more asterisk (*) characters
     *                          used for flexible matching. Patterns like "admin*" match values
     *                          starting with "admin", "*admin" matches values ending with "admin",
     *                          and "*admin*" matches values containing "admin" anywhere.
     */
    public function __construct(
        public readonly string $attribute,
        public readonly string $pattern,
    ) {}
}
