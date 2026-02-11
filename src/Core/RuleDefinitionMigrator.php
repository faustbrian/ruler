<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use function array_map;
use function is_array;
use function is_string;
use function str_contains;
use function str_starts_with;

/**
 * Migration helpers for persisted rule-definition payloads.
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class RuleDefinitionMigrator
{
    /**
     * Convert legacy dotted-string value references to explicit '@' references.
     *
     * Legacy payloads may store context references as plain strings (for
     * example "user.age"). This migration rewrites such values to the explicit
     * reference syntax ("@user.age") without changing literal strings.
     *
     * @param  array<string, mixed> $ruleDefinition
     * @return array<string, mixed>
     */
    public static function migrateLegacyStringReferences(array $ruleDefinition): array
    {
        if (isset($ruleDefinition['combinator'], $ruleDefinition['value']) && is_array($ruleDefinition['value'])) {
            /** @var array<int, array<string, mixed>> $operands */
            $operands = $ruleDefinition['value'];

            $ruleDefinition['value'] = array_map(
                self::migrateLegacyStringReferences(...),
                $operands,
            );

            return $ruleDefinition;
        }

        if (!isset($ruleDefinition['value']) || !is_string($ruleDefinition['value'])) {
            return $ruleDefinition;
        }

        if (str_starts_with($ruleDefinition['value'], '@')) {
            return $ruleDefinition;
        }

        if (!str_contains($ruleDefinition['value'], '.')) {
            return $ruleDefinition;
        }

        $ruleDefinition['value'] = '@'.$ruleDefinition['value'];

        return $ruleDefinition;
    }
}
