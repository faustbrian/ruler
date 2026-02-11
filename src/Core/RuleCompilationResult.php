<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Exceptions\RuleEvaluatorException;
use Cline\Ruler\Exceptions\RuleUnavailableException;

use function throw_unless;

/**
 * Represents a non-throwing rule compilation result.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class RuleCompilationResult
{
    private function __construct(
        private ?Rule $rule,
        private ?RuleEvaluatorException $error,
    ) {}

    public static function success(Rule $rule): self
    {
        return new self($rule, null);
    }

    public static function failure(RuleEvaluatorException $error): self
    {
        return new self(null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->rule instanceof Rule;
    }

    public function getRule(): Rule
    {
        throw_unless($this->rule instanceof Rule, RuleUnavailableException::compilationFailed());

        return $this->rule;
    }

    public function getError(): ?RuleEvaluatorException
    {
        return $this->error;
    }
}
