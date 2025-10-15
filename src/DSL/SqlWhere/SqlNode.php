<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\SqlWhere;

/**
 * Abstract base class for SQL WHERE clause AST nodes.
 *
 * Serves as the root type for all nodes in the abstract syntax tree
 * produced by parsing SQL WHERE clause expressions. Each concrete
 * subclass represents a specific SQL construct (comparison, logical
 * operation, field reference, literal, etc.).
 *
 * All nodes are immutable readonly objects to ensure thread safety
 * and prevent accidental modification during compilation or evaluation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 *
 * @see ComparisonNode For binary comparison operations (=, !=, <, >, <=, >=)
 * @see LogicalNode For logical operations (AND, OR, NOT)
 * @see FieldNode For field/column references
 * @see LiteralNode For constant values
 * @see InNode For IN/NOT IN operations
 * @see LikeNode For LIKE/NOT LIKE pattern matching
 * @see NullNode For IS NULL/IS NOT NULL checks
 */
abstract readonly class SqlNode {}
