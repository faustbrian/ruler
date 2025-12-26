<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\GraphQL;

/**
 * Base class for all GraphQL query AST nodes.
 *
 * GraphQLNode serves as the abstract base for all nodes in the GraphQL query
 * abstract syntax tree (AST). Concrete implementations represent different
 * types of query operations (comparisons, logical combinators, etc.) that
 * are constructed during GraphQL query parsing and used to build rule
 * propositions for evaluation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class GraphQLNode {}
