<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Simple in-memory cache for compiled rule graphs.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InMemoryCompiledRuleCache implements CompiledRuleCache
{
    /** @var array<string, Rule> */
    private array $compiledRules = [];

    public function get(string $key): ?Rule
    {
        return $this->compiledRules[$key] ?? null;
    }

    public function put(string $key, Rule $rule): void
    {
        $this->compiledRules[$key] = $rule;
    }
}
