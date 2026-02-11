<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Builder\VariableProperty;
use Cline\Ruler\Core\Definition\CombinatorRuleDefinition;
use Cline\Ruler\Core\Definition\ComparisonRuleDefinition;
use Cline\Ruler\Core\Definition\RuleCombinator;
use Cline\Ruler\Core\Definition\RuleDefinition;
use Cline\Ruler\Core\Definition\RuleDefinitionParser;
use Cline\Ruler\Exceptions\RuleEvaluatorException;
use Cline\Ruler\Variables\ContextValueReference;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;
use Throwable;

use const JSON_THROW_ON_ERROR;

use function array_map;
use function array_reduce;
use function explode;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function mb_substr;
use function str_contains;
use function str_starts_with;
use function ucfirst;

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
    ): RuleEvaluatorCompilationResult {
        try {
            $definition = RuleDefinitionParser::fromArray($rules);
            $evaluator = new self(
                $rules,
                $definition,
                $compiledRuleCache ?? new InMemoryCompiledRuleCache(),
                $compiledRuleKeyGenerator ?? new CanonicalJsonCompiledRuleKeyGenerator(),
            );
            $evaluator->getCompiledRule();

            return RuleEvaluatorCompilationResult::success($evaluator);
        } catch (RuleEvaluatorException $exception) {
            return RuleEvaluatorCompilationResult::failure($exception);
        } catch (Throwable $exception) {
            return RuleEvaluatorCompilationResult::failure(
                RuleEvaluatorException::invalidRuleStructure(
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
    ): RuleEvaluatorCompilationResult {
        try {
            $decoded = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return RuleEvaluatorCompilationResult::failure(
                    RuleEvaluatorException::invalidRuleStructure(
                        'JSON rules must decode to an object',
                        ['rules'],
                        ['format' => 'json'],
                    ),
                );
            }

            /** @var array<string, mixed> $decoded */
            return self::compileFromArray($decoded, $compiledRuleCache, $compiledRuleKeyGenerator);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
                RuleEvaluatorException::invalidRuleStructure(
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
    ): RuleEvaluatorCompilationResult {
        try {
            $contents = file_get_contents($rules);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
                RuleEvaluatorException::invalidRuleStructure(
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
                RuleEvaluatorException::invalidRuleStructure(
                    'Unable to read JSON rule file',
                    ['rules'],
                    [
                        'format' => 'json',
                        'file' => $rules,
                    ],
                ),
            );
        }

        return self::compileFromJson($contents, $compiledRuleCache, $compiledRuleKeyGenerator);
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
    ): RuleEvaluatorCompilationResult {
        try {
            $parsed = Yaml::parse($rules);

            if (!is_array($parsed)) {
                return RuleEvaluatorCompilationResult::failure(
                    RuleEvaluatorException::invalidRuleStructure(
                        'YAML rules must decode to an object',
                        ['rules'],
                        ['format' => 'yaml'],
                    ),
                );
            }

            /** @var array<string, mixed> $parsed */
            return self::compileFromArray($parsed, $compiledRuleCache, $compiledRuleKeyGenerator);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
                RuleEvaluatorException::invalidRuleStructure(
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
    ): RuleEvaluatorCompilationResult {
        try {
            $contents = file_get_contents($rules);
        } catch (Throwable $throwable) {
            return RuleEvaluatorCompilationResult::failure(
                RuleEvaluatorException::invalidRuleStructure(
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
                RuleEvaluatorException::invalidRuleStructure(
                    'Unable to read YAML rule file',
                    ['rules'],
                    [
                        'format' => 'yaml',
                        'file' => $rules,
                    ],
                ),
            );
        }

        return self::compileFromYaml($contents, $compiledRuleCache, $compiledRuleKeyGenerator);
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
                'JSON values must decode to an object',
                ['values'],
                ['format' => 'json'],
            );
        }

        return $this->evaluateFromArray($decoded);
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
                'YAML values must decode to an object',
                ['values'],
                ['format' => 'yaml'],
            );
        }

        return $this->evaluateFromArray($parsed);
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
            throw RuleEvaluatorException::runtimeEvaluationFailed(
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
     * Recursively builds a proposition from a rule definition.
     *
     * Processes rule definitions containing either combinators (and, or, xor, not)
     * or operators (comparison, arithmetic, set operations) and constructs the
     * corresponding proposition tree. Handles nested rules by recursively building
     * sub-propositions for complex logical expressions.
     *
     * @param RuleDefinition $definition  Typed rule definition containing either
     *                                    combinator nodes or comparison nodes
     * @param RuleBuilder    $ruleBuilder Builder instance used to construct propositions
     *                                    and access variable values through array syntax
     *
     * @throws RuleEvaluatorException When the rule structure is invalid, the NOT
     *                                combinator has multiple values, or an unsupported
     *                                combinator is encountered
     *
     * @return Proposition Constructed proposition representing the rule definition
     *                     logic
     */
    private static function proposition(RuleDefinition $definition, RuleBuilder $ruleBuilder): Proposition
    {
        if ($definition instanceof CombinatorRuleDefinition) {
            $method = 'logical'.ucfirst($definition->combinator->value);

            if ($definition->combinator === RuleCombinator::Not) {
                return $ruleBuilder->{$method}(self::proposition($definition->operands[0], $ruleBuilder));
            }

            return $ruleBuilder->{$method}(
                ...array_map(
                    fn (RuleDefinition $subRule): Proposition => self::proposition($subRule, $ruleBuilder),
                    $definition->operands,
                ),
            );
        }

        if ($definition instanceof ComparisonRuleDefinition) {
            // Resolve value: supports dot notation, direct variable reference, or literal
            $value = $definition->value;

            if (is_string($value) && str_starts_with($value, '@')) {
                $value = new ContextValueReference(mb_substr($value, 1));
            }

            // Resolve field: supports dot notation for nested field access
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
                throw RuleEvaluatorException::unknownOperator(
                    $definition->operator,
                    $definition->field,
                    ['operator'],
                    $exception,
                );
            }

            assert($result instanceof Proposition);

            return $result;
        }

        throw RuleEvaluatorException::invalidRuleStructure();
    }

    /**
     * Compile and cache the rule definition as an executable Rule instance.
     */
    private function getCompiledRule(): Rule
    {
        $key = $this->compiledRuleKeyGenerator->generate($this->rules);
        $cachedRule = $this->compiledRuleCache->get($key);

        if ($cachedRule instanceof Rule) {
            return $cachedRule;
        }

        $ruleBuilder = new RuleBuilder();
        $proposition = self::proposition($this->definition, $ruleBuilder);

        $rule = $ruleBuilder->create($proposition, $key);
        $this->compiledRuleCache->put($key, $rule);

        return $rule;
    }
}
