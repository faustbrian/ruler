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
use Cline\Ruler\Exceptions\RuntimeEvaluationFailedException;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;
use Throwable;

use const JSON_THROW_ON_ERROR;

use function array_keys;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;

/**
 * Evaluates propositional rules against data from various sources.
 *
 * Provides a flexible rule evaluation system that accepts rule definitions
 * from arrays, JSON, or YAML formats and evaluates them against data from
 * arrays, JSON, YAML, or Laravel HTTP requests. Supports complex rule
 * structures with combinators (and, or, xor, not) and various operators.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RuleEvaluator
{
    /**
     * Creates a new proposition evaluator.
     *
     * @param array<string, mixed> $rules Rule definition array containing the propositional
     *                                    logic structure with combinators and operators to
     *                                    evaluate against input data
     */
    private function __construct(
        private array $rules,
        private RuleDefinition $definition,
        private CompiledRuleCache $compiledRuleCache,
        private CompiledRuleKeyGenerator $compiledRuleKeyGenerator,
        private RuleCompileOptions $options,
    ) {}

    /**
     * Compile an evaluator from a rule definition array without throwing.
     *
     * @param  array<string, mixed>           $rules                    Rule definition array containing combinators,
     *                                                                  operators, fields, and values that define the
     *                                                                  propositional logic to evaluate
     * @param  null|CompiledRuleCache         $compiledRuleCache        Optional shared compiled-rule cache.
     *                                                                  When omitted, this evaluator owns an isolated
     *                                                                  in-memory cache for its lifetime.
     * @param  null|CompiledRuleKeyGenerator  $compiledRuleKeyGenerator Optional cache
     *                                                                  key strategy for compiled rules.
     * @return RuleEvaluatorCompilationResult Compilation result containing either evaluator or structured error
     */
    public static function compileFromArray(
        array $rules,
        ?CompiledRuleCache $compiledRuleCache = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleEvaluatorCompilationResult {
        try {
            $definition = RuleDefinitionParser::fromArray($rules);
            $evaluator = new self(
                $rules,
                $definition,
                $compiledRuleCache ?? new InMemoryCompiledRuleCache(),
                $compiledRuleKeyGenerator ?? new CanonicalJsonCompiledRuleKeyGenerator(),
                $options ?? RuleCompileOptions::default(),
            );
            $evaluator->getCompiledRule();

            return RuleEvaluatorCompilationResult::success($evaluator);
        } catch (RuleEvaluatorException $exception) {
            return RuleEvaluatorCompilationResult::failure($exception);
        } catch (Throwable $exception) {
            return RuleEvaluatorCompilationResult::failure(
                InvalidRuleStructureException::forReason(
                    'Rule compilation failed',
                    ['rules'],
                    ['reason' => $exception->getMessage()],
                ),
            );
        }
    }

    /**
     * Compile an evaluator from JSON rules without throwing.
     *
     * @param  string                         $rules                    JSON-encoded rule definition containing the propositional
     *                                                                  logic structure to evaluate
     * @param  null|CompiledRuleCache         $compiledRuleCache        Optional shared compiled-rule cache.
     * @param  null|CompiledRuleKeyGenerator  $compiledRuleKeyGenerator Optional cache
     *                                                                  key strategy for compiled rules.
     * @return RuleEvaluatorCompilationResult Compilation result containing either evaluator or structured error
     */
    public static function compileFromJson(
        string $rules,
        ?CompiledRuleCache $compiledRuleCache = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleEvaluatorCompilationResult {
        try {
            $decoded = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return RuleEvaluatorCompilationResult::failure(
                    InvalidRuleStructureException::forReason(
                        'JSON rules must decode to an object',
                        ['rules'],
                        ['format' => 'json'],
                    ),
                );
            }

            /** @var array<string, mixed> $decoded */
            return self::compileFromArray($decoded, $compiledRuleCache, $compiledRuleKeyGenerator, $options);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
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

    /**
     * Compile an evaluator from a JSON rule definition file without throwing.
     *
     * @param  string                         $rules                    File path to a JSON file containing the rule definition
     * @param  null|CompiledRuleCache         $compiledRuleCache        Optional shared compiled-rule cache.
     * @param  null|CompiledRuleKeyGenerator  $compiledRuleKeyGenerator Optional cache
     *                                                                  key strategy for compiled rules.
     * @return RuleEvaluatorCompilationResult Compilation result containing either evaluator or structured error
     */
    public static function compileFromJsonFile(
        string $rules,
        ?CompiledRuleCache $compiledRuleCache = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleEvaluatorCompilationResult {
        try {
            $contents = file_get_contents($rules);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
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
            return RuleEvaluatorCompilationResult::failure(
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

        return self::compileFromJson($contents, $compiledRuleCache, $compiledRuleKeyGenerator, $options);
    }

    /**
     * Compile an evaluator from YAML rules without throwing.
     *
     * @param  string                         $rules                    YAML-formatted rule definition containing the propositional
     *                                                                  logic structure to evaluate
     * @param  null|CompiledRuleCache         $compiledRuleCache        Optional shared compiled-rule cache.
     * @param  null|CompiledRuleKeyGenerator  $compiledRuleKeyGenerator Optional cache
     *                                                                  key strategy for compiled rules.
     * @return RuleEvaluatorCompilationResult Compilation result containing either evaluator or structured error
     */
    public static function compileFromYaml(
        string $rules,
        ?CompiledRuleCache $compiledRuleCache = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleEvaluatorCompilationResult {
        try {
            $parsed = Yaml::parse($rules);

            if (!is_array($parsed)) {
                return RuleEvaluatorCompilationResult::failure(
                    InvalidRuleStructureException::forReason(
                        'YAML rules must decode to an object',
                        ['rules'],
                        ['format' => 'yaml'],
                    ),
                );
            }

            /** @var array<string, mixed> $parsed */
            return self::compileFromArray($parsed, $compiledRuleCache, $compiledRuleKeyGenerator, $options);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
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

    /**
     * Compile an evaluator from a YAML rule definition file without throwing.
     *
     * @param  string                         $rules                    File path to a YAML file containing the rule definition
     * @param  null|CompiledRuleCache         $compiledRuleCache        Optional shared compiled-rule cache.
     * @param  null|CompiledRuleKeyGenerator  $compiledRuleKeyGenerator Optional cache
     *                                                                  key strategy for compiled rules.
     * @return RuleEvaluatorCompilationResult Compilation result containing either evaluator or structured error
     */
    public static function compileFromYamlFile(
        string $rules,
        ?CompiledRuleCache $compiledRuleCache = null,
        ?CompiledRuleKeyGenerator $compiledRuleKeyGenerator = null,
        ?RuleCompileOptions $options = null,
    ): RuleEvaluatorCompilationResult {
        try {
            $contents = file_get_contents($rules);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
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
            return RuleEvaluatorCompilationResult::failure(
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

        return self::compileFromYaml($contents, $compiledRuleCache, $compiledRuleKeyGenerator, $options);
    }

    /**
     * Evaluates the rules against an array of values.
     *
     * Constructs the rule proposition from the configured rules and evaluates
     * it against the provided values array. This is the primary evaluation
     * method used by all other evaluation methods.
     *
     * @param array<string, mixed> $values Data values to evaluate the rules against,
     *                                     typically containing field names as keys and
     *                                     their corresponding values for comparison
     *
     * @throws RuleEvaluatorException When the rule structure is invalid or contains
     *                                unsupported combinators or operators
     *
     * @return RuleEvaluatorReport Structured report of rule evaluation and execution details
     */
    public function evaluateFromArray(array $values): RuleEvaluatorReport
    {
        try {
            $context = new Context($values);
            $ruleResult = $this->getCompiledRule()
                ->execute($context);
        } catch (RuleEvaluatorException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RuntimeEvaluationFailedException::forReason(
                'Rule evaluation failed',
                [],
                ['values' => $values],
                $exception,
            );
        }

        return new RuleEvaluatorReport(
            $ruleResult->matched,
            $ruleResult,
            $values,
        );
    }

    /**
     * Evaluates the rules against a JSON-encoded values string.
     *
     * @param string $values JSON-encoded data values to evaluate the rules against
     *
     * @throws RuleEvaluatorException When the rule structure is invalid or contains
     *                                unsupported combinators or operators
     *
     * @return RuleEvaluatorReport Structured report of rule evaluation and execution details
     */
    public function evaluateFromJson(string $values): RuleEvaluatorReport
    {
        try {
            $decoded = json_decode($values, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            throw RuntimeEvaluationFailedException::forReason(
                'Invalid JSON values payload',
                ['values'],
                [
                    'format' => 'json',
                    'reason' => $throwable->getMessage(),
                ],
                $throwable,
            );
        }

        if (!is_array($decoded)) {
            throw RuntimeEvaluationFailedException::forReason(
                'JSON values must decode to an object',
                ['values'],
                ['format' => 'json'],
            );
        }

        return $this->evaluateFromArray($this->assertStringKeyedArray($decoded, 'json'));
    }

    /**
     * Evaluates the rules against values from a JSON file.
     *
     * @param string $values File path to a JSON file containing the data values
     *
     * @throws RuleEvaluatorException When the rule structure is invalid or contains
     *                                unsupported combinators or operators
     *
     * @return RuleEvaluatorReport Structured report of rule evaluation and execution details
     */
    public function evaluateFromJsonFile(string $values): RuleEvaluatorReport
    {
        try {
            $contents = file_get_contents($values);
        } catch (Throwable $throwable) {
            throw RuntimeEvaluationFailedException::forReason(
                'Unable to read JSON values file',
                ['values'],
                [
                    'format' => 'json',
                    'file' => $values,
                    'reason' => $throwable->getMessage(),
                ],
                $throwable,
            );
        }

        if (!is_string($contents)) {
            throw RuntimeEvaluationFailedException::forReason(
                'Unable to read JSON values file',
                ['values'],
                [
                    'format' => 'json',
                    'file' => $values,
                ],
            );
        }

        return $this->evaluateFromJson($contents);
    }

    /**
     * Evaluates the rules against a YAML-formatted values string.
     *
     * @param string $values YAML-formatted data values to evaluate the rules against
     *
     * @throws RuleEvaluatorException When the rule structure is invalid or contains
     *                                unsupported combinators or operators
     *
     * @return RuleEvaluatorReport Structured report of rule evaluation and execution details
     */
    public function evaluateFromYaml(string $values): RuleEvaluatorReport
    {
        try {
            $parsed = Yaml::parse($values);
        } catch (Throwable $throwable) {
            throw RuntimeEvaluationFailedException::forReason(
                'Invalid YAML values payload',
                ['values'],
                [
                    'format' => 'yaml',
                    'reason' => $throwable->getMessage(),
                ],
                $throwable,
            );
        }

        if (!is_array($parsed)) {
            throw RuntimeEvaluationFailedException::forReason(
                'YAML values must decode to an object',
                ['values'],
                ['format' => 'yaml'],
            );
        }

        return $this->evaluateFromArray($this->assertStringKeyedArray($parsed, 'yaml'));
    }

    /**
     * Evaluates the rules against values from a YAML file.
     *
     * @param string $values File path to a YAML file containing the data values
     *
     * @throws RuleEvaluatorException When the rule structure is invalid or contains
     *                                unsupported combinators or operators
     *
     * @return RuleEvaluatorReport Structured report of rule evaluation and execution details
     */
    public function evaluateFromYamlFile(string $values): RuleEvaluatorReport
    {
        try {
            $contents = file_get_contents($values);
        } catch (Throwable $throwable) {
            throw RuntimeEvaluationFailedException::forReason(
                'Unable to read YAML values file',
                ['values'],
                [
                    'format' => 'yaml',
                    'file' => $values,
                    'reason' => $throwable->getMessage(),
                ],
                $throwable,
            );
        }

        if (!is_string($contents)) {
            throw RuntimeEvaluationFailedException::forReason(
                'Unable to read YAML values file',
                ['values'],
                [
                    'format' => 'yaml',
                    'file' => $values,
                ],
            );
        }

        return $this->evaluateFromYaml($contents);
    }

    /**
     * Evaluates the rules against Laravel HTTP request data.
     *
     * Extracts all request input data and evaluates the configured rules
     * against it. Useful for validating complex business rules against
     * incoming HTTP request payloads.
     *
     * @param Request $request Laravel HTTP request containing the data to evaluate
     *
     * @throws RuleEvaluatorException When the rule structure is invalid or contains
     *                                unsupported combinators or operators
     *
     * @return RuleEvaluatorReport Structured report of rule evaluation and execution details
     */
    public function evaluateFromRequest(Request $request): RuleEvaluatorReport
    {
        /** @var array<string, mixed> $requestData */
        $requestData = $request->all();

        return $this->evaluateFromArray($requestData);
    }

    /**
     * @param  array<mixed, mixed>  $values
     * @param  'json'|'yaml'        $format
     * @return array<string, mixed>
     */
    private function assertStringKeyedArray(array $values, string $format): array
    {
        foreach (array_keys($values) as $key) {
            if (is_string($key)) {
                continue;
            }

            throw RuntimeEvaluationFailedException::forReason(
                'Values payload must decode to an object with string keys',
                ['values'],
                ['format' => $format],
            );
        }

        /** @var array<string, mixed> $values */
        return $values;
    }

    /**
     * Compile and cache the rule definition as an executable Rule instance.
     */
    private function getCompiledRule(): Rule
    {
        $key = $this->compiledRuleKeyGenerator->generate($this->getCompiledRuleKeyPayload());
        $cachedRule = $this->compiledRuleCache->get($key);

        if ($cachedRule instanceof Rule) {
            return $cachedRule;
        }

        $ruleBuilder = $this->options->applyToRuleBuilder(
            new RuleBuilder(),
        );
        $proposition = RuleDefinitionPropositionCompiler::compile(
            $this->definition,
            $ruleBuilder,
        );

        $rule = $ruleBuilder->create($proposition, RuleIds::fromString($key));
        $this->compiledRuleCache->put($key, $rule);

        return $rule;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCompiledRuleKeyPayload(): array
    {
        if ($this->options->getOperatorNamespaces() === []) {
            return $this->rules;
        }

        return [
            'rules' => $this->rules,
            'operatorNamespaces' => $this->options->getOperatorNamespaces(),
        ];
    }
}
