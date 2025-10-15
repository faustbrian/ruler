<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SqlWhere;

/**
 * Represents an IN or NOT IN operation in SQL WHERE clause.
 *
 * Tests whether a field value matches any value in a provided list.
 * When negated, tests that the field value does not match any value
 * in the list.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class InNode extends SqlNode
{
    /**
     * Create a new IN/NOT IN operation node.
     *
     * @param SqlNode           $field   Field reference to test against the value list. Typically
     *                                   a FieldNode representing a database column that will be
     *                                   checked for membership in the values array.
     * @param array<int, mixed> $values  Array of values to test against. Can contain strings, numbers,
     *                                   booleans, or null values. The field is checked to see if it
     *                                   matches any value in this list.
     * @param bool              $negated When true, produces a NOT IN operation that checks the field
     *                                   does not match any value in the list. When false (default),
     *                                   produces an IN operation. Defaults to false.
     */
    public function __construct(
        public SqlNode $field,
        public array $values,
        public bool $negated = false,
    ) {}
}
