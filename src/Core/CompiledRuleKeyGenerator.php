<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Generates cache keys for compiled rule definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface CompiledRuleKeyGenerator
{
    /**
     * Generate a deterministic cache key for a rule definition.
     *
     * @param array<string, mixed> $rules
     */
    public function generate(array $rules): string;
}
