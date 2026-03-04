<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

/**
 * Represents an approximate match node in LDAP filter AST.
 *
 * Handles the ~= operator for fuzzy/approximate matching in LDAP filters.
 * Compiled to case-insensitive substring matching in the evaluation engine.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ApproximateNode extends LDAPNode
{
    /**
     * Create a new approximate match node.
     *
     * @param string $attribute LDAP attribute name or field path to perform the approximate
     *                          match against. Supports dot-notation for nested field access
     *                          when compiled for evaluation.
     * @param string $value     Value to approximately match. The compilation process converts
     *                          this to a case-insensitive regex pattern for fuzzy matching
     *                          that allows for variations in the attribute value.
     */
    public function __construct(
        public readonly string $attribute,
        public readonly string $value,
    ) {}
}
