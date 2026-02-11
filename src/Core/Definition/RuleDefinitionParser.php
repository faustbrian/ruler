<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core\Definition;

use Cline\Ruler\Exceptions\RuleEvaluatorException;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function throw_if;
use function throw_unless;

/**
 * Parses array rule payloads into typed AST definitions.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RuleDefinitionParser
{
    /**
     * Parse raw array rules into typed definitions.
     *
     * @param array<string, mixed> $rules
     */
    public static function fromArray(array $rules): RuleDefinition
    {
        return self::parse($rules);
    }

    /**
     * @param array<string, mixed>   $rule
     * @param array<int, int|string> $path
     */
    private static function parse(array $rule, array $path = []): RuleDefinition
    {
        if (array_key_exists('combinator', $rule)) {
            throw_unless(
                is_string($rule['combinator']),
                RuleEvaluatorException::invalidRuleStructure('Combinator must be a string', [...$path, 'combinator']),
            );
            throw_unless(
                is_array($rule['value'] ?? null),
                RuleEvaluatorException::invalidRuleStructure('Combinator value must be an array', [...$path, 'value']),
            );

            $combinator = RuleCombinator::tryFrom($rule['combinator']);
            throw_if(
                $combinator === null,
                RuleEvaluatorException::invalidCombinator($rule['combinator'], [...$path, 'combinator']),
            );

            $operands = [];

            /** @var mixed $operand */
            foreach ($rule['value'] as $operandIndex => $operand) {
                throw_unless(
                    is_array($operand),
                    RuleEvaluatorException::invalidRuleStructure(
                        'Combinator operands must be rule objects',
                        [...$path, 'value', $operandIndex],
                    ),
                );

                /** @var array<string, mixed> $operand */
                $operands[] = self::parse($operand, [...$path, 'value', $operandIndex]);
            }

            return new CombinatorRuleDefinition($combinator, $operands);
        }

        if (array_key_exists('operator', $rule)) {
            throw_unless(
                is_string($rule['field'] ?? null) || is_int($rule['field'] ?? null),
                RuleEvaluatorException::invalidRuleStructure(
                    'Operator rule field must be a string or integer',
                    [...$path, 'field'],
                ),
            );
            throw_unless(
                is_string($rule['operator']),
                RuleEvaluatorException::invalidRuleStructure('Operator must be a string', [...$path, 'operator']),
            );
            throw_unless(
                array_key_exists('value', $rule),
                RuleEvaluatorException::invalidRuleStructure('Operator rule must include value', [...$path, 'value']),
            );

            $field = is_string($rule['field']) ? $rule['field'] : sprintf('%d', $rule['field']);

            return new ComparisonRuleDefinition(
                $field,
                $rule['operator'],
                $rule['value'],
            );
        }

        throw RuleEvaluatorException::invalidRuleStructure('Invalid rule structure', $path);
    }
}
