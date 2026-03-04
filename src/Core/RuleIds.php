<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Utility factory methods for building RuleId instances.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RuleIds
{
    public static function fromString(string $value): RuleId
    {
        return RuleId::fromString($value);
    }

    /**
     * @param array<string, mixed> $definition
     */
    public static function fromDefinition(
        array $definition,
        ?CompiledRuleKeyGenerator $keyGenerator = null,
    ): RuleId {
        return RuleId::fromDefinition($definition, $keyGenerator);
    }

    public static function random(): RuleId
    {
        return RuleId::random();
    }
}
