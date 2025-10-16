<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Builder\VariableProperty;

use function array_key_exists;
use function count;
use function explode;

/**
 * Resolves dot-notation field paths to Variable and VariableProperty chains.
 *
 * Parses field expressions like "user.age" or "http.request.uri.path" and
 * creates the appropriate Variable and VariableProperty chain for evaluation.
 * Fields are cached to ensure the same Variable instance is reused.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class FieldResolver
{
    /**
     * Cache of resolved field paths to Variable instances.
     *
     * @var array<string, Variable|VariableProperty>
     */
    private array $cache = [];

    /**
     * Create a new FieldResolver instance.
     *
     * @param RuleBuilder $ruleBuilder The RuleBuilder instance used for creating
     *                                 Variables when resolving field paths. The same
     *                                 RuleBuilder must be used throughout a parsing
     *                                 session to ensure Variable instance consistency.
     */
    public function __construct(
        private readonly RuleBuilder $ruleBuilder,
    ) {}

    /**
     * Resolve a dot-notation field path to a Variable or VariableProperty.
     *
     * Parses field expressions and creates the appropriate Variable or nested
     * VariableProperty chain. Results are cached to ensure the same Variable
     * instance is returned for repeated field references, which is critical
     * for correct rule evaluation.
     *
     * Examples:
     * - "age" → Variable
     * - "user.age" → VariableProperty
     * - "http.request.uri.path" → Nested VariableProperty chain
     *
     * @param  string                    $fieldPath Dot-notation field path (e.g., "user.age", "status")
     * @return Variable|VariableProperty The resolved Variable or VariableProperty chain
     */
    public function resolve(string $fieldPath): Variable|VariableProperty
    {
        if (array_key_exists($fieldPath, $this->cache)) {
            return $this->cache[$fieldPath];
        }

        $parts = explode('.', $fieldPath);
        $variable = $this->ruleBuilder[$parts[0]];

        if (count($parts) === 1) {
            $this->cache[$fieldPath] = $variable;

            return $variable;
        }

        $current = $variable;
        $counter = count($parts);

        for ($i = 1; $i < $counter; ++$i) {
            $current = $current[$parts[$i]];
        }

        $this->cache[$fieldPath] = $current;

        return $current;
    }

    /**
     * Clear the resolver cache.
     *
     * Removes all cached field path resolutions. This is primarily useful
     * for testing scenarios or when you need to ensure fresh Variable
     * instances for a new parsing session.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get the RuleBuilder instance.
     *
     * Returns the RuleBuilder used for creating Variables. This is needed
     * by the RuleCompiler for wrapping mathematical operators in Variables.
     *
     * @return RuleBuilder The RuleBuilder instance
     */
    public function getRuleBuilder(): RuleBuilder
    {
        return $this->ruleBuilder;
    }
}
