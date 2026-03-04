<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Exceptions\EmptyRuleIdException;
use Stringable;

use function bin2hex;
use function mb_trim;
use function random_bytes;
use function throw_if;

/**
 * Immutable value object representing a rule identifier.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RuleId implements Stringable
{
    private function __construct(
        private string $value,
    ) {
        throw_if(mb_trim($this->value) === '', EmptyRuleIdException::create());
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @param array<string, mixed> $definition
     */
    public static function fromDefinition(
        array $definition,
        ?CompiledRuleKeyGenerator $keyGenerator = null,
    ): self {
        $generator = $keyGenerator ?? new CanonicalJsonCompiledRuleKeyGenerator();

        return new self($generator->generate($definition));
    }

    public static function random(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
