<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Represents a string operation in GraphQL filter queries.
 *
 * StringNode encapsulates string matching operations including substring searches,
 * prefix/suffix matching, and pattern matching. These nodes support both case-sensitive
 * and case-insensitive matching modes, enabling flexible text filtering in queries.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class StringNode extends GraphQLNode
{
    /**
     * Create a new string operation node.
     *
     * @param string $operator        The string operation to perform. Supported values: "contains"
     *                                tests for substring presence, "notContains" tests for substring
     *                                absence, "startsWith" tests for prefix match, "endsWith" tests
     *                                for suffix match, "match" performs regex pattern matching. The
     *                                operator determines which string operator class is instantiated.
     * @param string $field           The field name to access from the evaluation context. Supports
     *                                dot notation for nested field access (e.g., "user.name"). The
     *                                field's string value is tested against the provided value using
     *                                the specified operator.
     * @param string $value           The string value to test against. For contains/notContains/
     *                                startsWith/endsWith, this is the literal substring to find. For
     *                                match operations, this is the regex pattern (delimiters added
     *                                automatically during compilation).
     * @param bool   $caseInsensitive Whether to perform case-insensitive matching. Only applies to
     *                                contains and notContains operations. When true, matching ignores
     *                                character case differences. Defaults to false for case-sensitive
     *                                matching behavior.
     */
    public function __construct(
        public readonly string $operator,
        public readonly string $field,
        public readonly string $value,
        public readonly bool $caseInsensitive = false,
    ) {}
}
