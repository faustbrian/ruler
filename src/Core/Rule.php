<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Closure;
use RuntimeException;

use function bin2hex;
use function random_bytes;
use function sprintf;
use function throw_if;

/**
 * Represents a conditional rule with an optional action.
 *
 * A Rule combines a propositional condition with an optional callback action
 * that executes when the condition evaluates to true. Rules are the core
 * building blocks for implementing business logic and conditional workflows
 * in the Ruler library.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class Rule implements Proposition
{
    private string $id;

    /**
     * Create a new Rule instance.
     *
     * @param Proposition  $condition The propositional condition that determines whether
     *                                this rule is satisfied. The condition is evaluated
     *                                against a Context to produce a boolean result.
     * @param null|Closure $action    Optional callback to execute when the condition
     *                                evaluates to true. The callback must accept the
     *                                current Context as its first argument.
     */
    public function __construct(
        private Proposition $condition,
        /**
         * The optional action to execute when the rule condition is satisfied.
         *
         * @var null|Closure
         */
        private ?Closure $action = null,
        ?string $id = null,
        private ?string $name = null,
        private int $priority = 0,
        private bool $enabled = true,
        /**
         * User-defined metadata for rule governance and observability.
         *
         * @var array<string, mixed>
         */
        private array $metadata = [],
    ) {
        $this->id = $id ?? $this->nextAutoId();

        throw_if($this->id === '', RuntimeException::class, 'Rule id cannot be empty.');
    }

    /**
     * Evaluate the rule condition against the given context.
     *
     * @param  Context $context the context containing variable values and facts
     *                          used to evaluate the propositional condition
     * @return bool    true if the rule condition is satisfied, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->condition->evaluate($context);
    }

    /**
     * Execute the rule by evaluating its condition and running the action if satisfied.
     *
     * The rule's condition is first evaluated against the provided context. If the
     * condition evaluates to true and an action callback is defined, the action
     * is executed. The action must accept Context and return void.
     *
     * @param Context $context the context containing variable values and facts
     *                         used to evaluate the rule
     *
     * @return RuleExecutionResult Structured execution details for this rule
     */
    public function execute(Context $context): RuleExecutionResult
    {
        $matched = $this->evaluate($context);
        $actionExecuted = false;

        if ($matched && $this->action instanceof Closure) {
            ($this->action)($context);
            $actionExecuted = true;
        }

        return new RuleExecutionResult(
            $this->id,
            $this->name,
            $this->priority,
            $this->enabled,
            $matched,
            $actionExecuted,
        );
    }

    /**
     * Get the unique rule identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the human-readable rule name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the rule salience/priority used by conflict resolution.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Check whether this rule is enabled for evaluation.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get metadata attached to this rule.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a specific metadata value when present.
     */
    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    private function nextAutoId(): string
    {
        return sprintf('rule-auto-%s', bin2hex(random_bytes(8)));
    }
}
