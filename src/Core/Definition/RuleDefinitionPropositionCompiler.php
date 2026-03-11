<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core\Definition;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Builder\VariableProperty;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Exceptions\InvalidRuleStructureException;
use Cline\Ruler\Exceptions\UnknownRuleOperatorException;
use Cline\Ruler\Variables\ContextValueReference;
use Throwable;

use function array_map;
use function array_reduce;
use function assert;
use function explode;
use function is_string;
use function mb_substr;
use function str_contains;
use function str_starts_with;
use function ucfirst;

/**
 * Compiles typed rule definitions into executable propositions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RuleDefinitionPropositionCompiler
{
    public static function compile(
        RuleDefinition $definition,
        RuleBuilder $ruleBuilder,
    ): Proposition {
        if ($definition instanceof CombinatorRuleDefinition) {
            $method = 'logical'.ucfirst($definition->combinator->value);

            if ($definition->combinator === RuleCombinator::Not) {
                return $ruleBuilder->{$method}(self::compile($definition->operands[0], $ruleBuilder));
            }

            return $ruleBuilder->{$method}(
                ...array_map(
                    fn (RuleDefinition $subRule): Proposition => self::compile($subRule, $ruleBuilder),
                    $definition->operands,
                ),
            );
        }

        if ($definition instanceof ComparisonRuleDefinition) {
            $value = $definition->value;

            if (is_string($value) && str_starts_with($value, '@')) {
                $value = new ContextValueReference(mb_substr($value, 1));
            }

            $fieldString = $definition->field;

            /** @var BuilderVariable $builder */
            $builder = str_contains($fieldString, '.')
                ? array_reduce(
                    explode('.', $fieldString),
                    /**
                     * @param  BuilderVariable|RuleBuilder|VariableProperty $builder
                     * @return BuilderVariable|VariableProperty
                     */
                    static fn (mixed $builder, string $segment): mixed =>
                        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
                        $builder[$segment],
                    $ruleBuilder,
                )
                : $ruleBuilder[$fieldString];

            try {
                $result = $builder->{$definition->operator}($value);
            } catch (Throwable $exception) {
                throw UnknownRuleOperatorException::forOperator(
                    $definition->operator,
                    $definition->field,
                    ['operator'],
                    $exception,
                );
            }

            assert($result instanceof Proposition);

            return $result;
        }

        throw InvalidRuleStructureException::forReason();
    }
}
