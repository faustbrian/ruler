<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Exceptions\RuleEvaluatorException;
use LogicException;

use function throw_unless;

/**
 * Represents a non-throwing rule compilation result.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class RuleEvaluatorCompilationResult
{
    private function __construct(
        private ?RuleEvaluator $evaluator,
        private ?RuleEvaluatorException $error,
    ) {}

    public static function success(RuleEvaluator $evaluator): self
    {
        return new self($evaluator, null);
    }

    public static function failure(RuleEvaluatorException $error): self
    {
        return new self(null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->evaluator instanceof RuleEvaluator;
    }

    public function getEvaluator(): RuleEvaluator
    {
        throw_unless($this->evaluator instanceof RuleEvaluator, LogicException::class, 'Compilation failed; evaluator is unavailable.');

        return $this->evaluator;
    }

    public function getError(): ?RuleEvaluatorException
    {
        return $this->error;
    }
}
