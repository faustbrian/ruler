<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Exceptions\InvalidRuleCacheKeyException;
use JsonException;

use const JSON_THROW_ON_ERROR;

use function array_is_list;
use function array_map;
use function hash;
use function is_array;
use function json_encode;
use function ksort;

/**
 * Generates cache keys using canonicalized JSON and SHA-256 hashing.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class CanonicalJsonCompiledRuleKeyGenerator implements CompiledRuleKeyGenerator
{
    /**
     * @param array<string, mixed> $rules
     */
    public function generate(array $rules): string
    {
        try {
            $normalized = $this->normalize($rules);
            $payload = json_encode($normalized, JSON_THROW_ON_ERROR);

            return hash('sha256', $payload);
        } catch (JsonException $jsonException) {
            throw InvalidRuleCacheKeyException::forReason($jsonException->getMessage(), $jsonException);
        }
    }

    /**
     * Normalize nested data while preserving list order and sorting map keys.
     */
    private function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->normalize(...), $value);
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $this->normalize($item);
        }

        ksort($normalized);

        return $normalized;
    }
}
