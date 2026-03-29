<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Definition\RuleDefinition;
use Cline\Ruler\Core\Definition\RuleDefinitionParser;
use Cline\Ruler\Core\Definition\RuleDefinitionPropositionCompiler;
use Cline\Ruler\Exceptions\InvalidRuleStructureException;
use Cline\Ruler\Exceptions\RuleEvaluatorException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

use const JSON_THROW_ON_ERROR;

use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;

/**
 * Compiles serialized rule definitions into executable Rule instances.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RuleCompiler
{
    /**
     * Compile a rule from an array definition without throwing.
     *
     * @param array<string, mixed> $rules
     */
    public static function compileFromArray(
        array $rules,
        ?RuleId $ruleId = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleCompilationResult {
        try {
            $definition = RuleDefinitionParser::fromArray($rules);

            return self::compileDefinition(
                $definition,
                $ruleId ?? RuleIds::fromDefinition($rules, $compiledRuleKeyGenerator),
                $options,
            );
        } catch (RuleEvaluatorException $exception) {
            return RuleCompilationResult::failure($exception);
        } catch (Throwable $exception) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Rule compilation failed',
                    ['rules'],
                    ['reason' => $exception->getMessage()],
                ),
            );
        }
    }

    /**
     * Compile a rule from a typed definition without throwing.
     */
    public static function compileDefinition(
        RuleDefinition $definition,
        RuleId $ruleId,
        ?RuleCompileOptions $options = null,
    ): RuleCompilationResult {
        try {
            $builder = self::createRuleBuilder($options);
            $proposition = RuleDefinitionPropositionCompiler::compile($definition, $builder);
            $rule = $builder->create($proposition, $ruleId);

            return RuleCompilationResult::success($rule);
        } catch (RuleEvaluatorException $exception) {
            return RuleCompilationResult::failure($exception);
        } catch (Throwable $exception) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Rule compilation failed',
                    ['rules'],
                    ['reason' => $exception->getMessage()],
                ),
            );
        }
    }

    public static function compileFromJson(
        string $rules,
        ?RuleId $ruleId = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleCompilationResult {
        try {
            $decoded = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return RuleCompilationResult::failure(
                    InvalidRuleStructureException::forReason(
                        'JSON rules must decode to an object',
                        ['rules'],
                        ['format' => 'json'],
                    ),
                );
            }

            /** @var array<string, mixed> $decoded */
            return self::compileFromArray($decoded, $ruleId, $compiledRuleKeyGenerator, $options);
        } catch (Throwable $throwable) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Invalid JSON rule payload',
                    ['rules'],
                    [
                        'format' => 'json',
                        'reason' => $throwable->getMessage(),
                    ],
                ),
            );
        }
    }

    public static function compileFromJsonFile(
        string $rules,
        ?RuleId $ruleId = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleCompilationResult {
        try {
            $contents = file_get_contents($rules);
        } catch (Throwable $throwable) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Unable to read JSON rule file',
                    ['rules'],
                    [
                        'format' => 'json',
                        'file' => $rules,
                        'reason' => $throwable->getMessage(),
                    ],
                ),
            );
        }

        if (!is_string($contents)) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Unable to read JSON rule file',
                    ['rules'],
                    [
                        'format' => 'json',
                        'file' => $rules,
                    ],
                ),
            );
        }

        return self::compileFromJson($contents, $ruleId, $compiledRuleKeyGenerator, $options);
    }

    public static function compileFromYaml(
        string $rules,
        ?RuleId $ruleId = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleCompilationResult {
        try {
            $parsed = Yaml::parse($rules);

            if (!is_array($parsed)) {
                return RuleCompilationResult::failure(
                    InvalidRuleStructureException::forReason(
                        'YAML rules must decode to an object',
                        ['rules'],
                        ['format' => 'yaml'],
                    ),
                );
            }

            /** @var array<string, mixed> $parsed */
            return self::compileFromArray($parsed, $ruleId, $compiledRuleKeyGenerator, $options);
        } catch (Throwable $throwable) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Invalid YAML rule payload',
                    ['rules'],
                    [
                        'format' => 'yaml',
                        'reason' => $throwable->getMessage(),
                    ],
                ),
            );
        }
    }

    public static function compileFromYamlFile(
        string $rules,
        ?RuleId $ruleId = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleCompilationResult {
        try {
            $contents = file_get_contents($rules);
        } catch (Throwable $throwable) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Unable to read YAML rule file',
                    ['rules'],
                    [
                        'format' => 'yaml',
                        'file' => $rules,
                        'reason' => $throwable->getMessage(),
                    ],
                ),
            );
        }

        if (!is_string($contents)) {
            return RuleCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Unable to read YAML rule file',
                    ['rules'],
                    [
                        'format' => 'yaml',
                        'file' => $rules,
                    ],
                ),
            );
        }

        return self::compileFromYaml($contents, $ruleId, $compiledRuleKeyGenerator, $options);
    }

    private static function createRuleBuilder(?RuleCompileOptions $options = null): RuleBuilder
    {
        return ($options ?? RuleCompileOptions::default())
            ->applyToRuleBuilder(
                new RuleBuilder(),
            );
    }
}
