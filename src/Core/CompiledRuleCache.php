<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Cache abstraction for compiled rule graphs.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface CompiledRuleCache
{
    /**
     * Fetch a compiled rule by cache key.
     */
    public function get(string $key): ?Rule;

    /**
     * Store a compiled rule by cache key.
     */
    public function put(string $key, Rule $rule): void;
}
