<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Builder\RuleBuilder;

use function array_unique;
use function array_values;

/**
 * Immutable compile-time options for persisted rule compilation.
 *
 * @psalm-immutable
 */
final readonly class RuleCompileOptions
{
    /**
     * @param array<int, string> $operatorNamespaces
     */
    private function __construct(
        private array $operatorNamespaces = [],
    ) {}

    public static function default(): self
    {
        return new self();
    }

    /**
     * @param array<int, string> $operatorNamespaces
     */
    public function withOperatorNamespaces(array $operatorNamespaces): self
    {
        return new self(array_values(array_unique($operatorNamespaces)));
    }

    /**
     * @return array<int, string>
     */
    public function getOperatorNamespaces(): array
    {
        return $this->operatorNamespaces;
    }

    public function applyToRuleBuilder(RuleBuilder $ruleBuilder): RuleBuilder
    {
        foreach ($this->operatorNamespaces as $namespace) {
            $ruleBuilder->registerOperatorNamespace($namespace);
        }

        return $ruleBuilder;
    }
}
