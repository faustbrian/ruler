<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\LDAP;

/**
 * Base class for LDAP filter AST nodes.
 *
 * Provides the foundation for all node types in the LDAP filter Abstract Syntax
 * Tree. Subclasses represent specific LDAP filter components like comparisons,
 * logical operators, wildcards, presence checks, and approximate matches.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class LDAPNode {}
