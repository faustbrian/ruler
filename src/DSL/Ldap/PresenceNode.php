<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Ldap;

/**
 * Represents a field presence check node in LDAP filter AST.
 *
 * Handles (field=*) syntax to check if an attribute exists and has any value.
 * In LDAP filters, this is commonly used to test for non-null attributes or
 * to verify that an entry contains a specific attribute regardless of its value.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class PresenceNode extends LdapNode
{
    /**
     * Create a new presence check node.
     *
     * @param string $attribute Field or attribute name to check for presence in the directory
     *                          entry. The presence check verifies that this attribute exists
     *                          and has at least one value, equivalent to the LDAP filter
     *                          syntax (attribute=*) which returns true for non-null attributes.
     */
    public function __construct(
        public readonly string $attribute,
    ) {}
}
