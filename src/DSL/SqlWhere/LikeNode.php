<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SqlWhere;

/**
 * Represents a LIKE or NOT LIKE operation in SQL WHERE clause.
 *
 * Performs pattern matching using SQL LIKE syntax where % matches any
 * sequence of characters and _ matches any single character. The pattern
 * is converted to a regular expression during compilation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class LikeNode extends SqlNode
{
    /**
     * Create a new LIKE/NOT LIKE operation node.
     *
     * @param SqlNode $field   Field reference to perform pattern matching against. Typically
     *                         a FieldNode representing a string column that will be tested
     *                         against the pattern using SQL LIKE semantics.
     * @param string  $pattern SQL LIKE pattern string supporting wildcards: % matches zero or
     *                         more characters, _ matches exactly one character. Backslash can
     *                         escape these wildcards. The pattern is converted to regex during
     *                         compilation for evaluation.
     * @param bool    $negated When true, produces a NOT LIKE operation that checks the field
     *                         does not match the pattern. When false (default), produces a
     *                         LIKE operation. Defaults to false.
     */
    public function __construct(
        public SqlNode $field,
        public string $pattern,
        public bool $negated = false,
    ) {}
}
