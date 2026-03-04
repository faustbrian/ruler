<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Enums;

/**
 * Defines rule conflict resolution strategies for RuleSet execution ordering.
 *
 * Conflict resolution determines execution order when multiple rules are
 * eligible to run in the same pass.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum ConflictResolutionStrategy: string
{
    /**
     * Execute rules in insertion order (first added, first executed).
     */
    case InsertionOrder = 'insertion_order';

    /**
     * Execute higher-priority rules first, then by insertion order.
     */
    case PriorityHighFirst = 'priority_high_first';

    /**
     * Execute lower-priority rules first, then by insertion order.
     */
    case PriorityLowFirst = 'priority_low_first';
}
