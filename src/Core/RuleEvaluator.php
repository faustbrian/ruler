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
use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Operator;
use Cline\Ruler\Exceptions\RuleEvaluatorException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

use const JSON_THROW_ON_ERROR;

use function array_key_exists;
use function array_map;
use function array_reduce;
use function assert;
use function count;
use function explode;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function str_contains;
use function throw_if;
use function throw_unless;
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
    ) {}

    /**
     * Creates an evaluator from a rule definition array.
     *
     * @param  array<string, mixed> $rules Rule definition array containing combinators,
     *                                     operators, fields, and values that define the
     *                                     propositional logic to evaluate
     * @return self                 New RuleEvaluator instance initialized with the provided rules
     */
    public static function createFromArray(array $rules): self
    {
        return new self($rules);
    }

    /**
     * Creates an evaluator from a JSON rule definition string.
     *
     * @param  string $rules JSON-encoded rule definition containing the propositional
     *                       logic structure to evaluate
     * @return self   New RuleEvaluator instance initialized with the parsed rules
     */
    public static function createFromJson(string $rules): self
    {
        $decoded = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($decoded));

        /** @var array<string, mixed> $decoded */
        return self::createFromArray($decoded);
    }

    /**
     * Creates an evaluator from a JSON file containing rule definitions.
     *
     * @param  string $rules File path to a JSON file containing the rule definition
     * @return self   New RuleEvaluator instance initialized with the parsed rules
     */
    public static function createFromJsonFile(string $rules): self
    {
        $contents = file_get_contents($rules);
        assert(is_string($contents));

        return self::createFromJson($contents);
    }

    /**
     * Creates an evaluator from a YAML rule definition string.
     *
     * @param  string $rules YAML-formatted rule definition containing the propositional
     *                       logic structure to evaluate
     * @return self   New RuleEvaluator instance initialized with the parsed rules
     */
    public static function createFromYaml(string $rules): self
    {
        $parsed = Yaml::parse($rules);
        assert(is_array($parsed));

        /** @var array<string, mixed> $parsed */
        return self::createFromArray($parsed);
    }

    /**
     * Creates an evaluator from a YAML file containing rule definitions.
     *
     * @param  string $rules File path to a YAML file containing the rule definition
     * @return self   New RuleEvaluator instance initialized with the parsed rules
     */
    public static function createFromYamlFile(string $rules): self
    {
        $contents = file_get_contents($rules);
        assert(is_string($contents));

        return self::createFromYaml($contents);
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
     * @return bool Returns true if the rule evaluation passes, false otherwise
     */
    public function evaluateFromArray(array $values): bool
    {
        $ruleBuilder = new RuleBuilder();
        $proposition = self::proposition($values, $this->rules, $ruleBuilder);
        assert($proposition instanceof Proposition);

        return $ruleBuilder
            ->create($proposition)
            ->evaluate(
                new Context($values),
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
     * @return bool Returns true if the rule evaluation passes, false otherwise
     */
    public function evaluateFromJson(string $values): bool
    {
        $decoded = json_decode($values, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($decoded));

        /** @var array<string, mixed> $decoded */
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
     * @return bool Returns true if the rule evaluation passes, false otherwise
     */
    public function evaluateFromJsonFile(string $values): bool
    {
        $contents = file_get_contents($values);
        assert(is_string($contents));

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
     * @return bool Returns true if the rule evaluation passes, false otherwise
     */
    public function evaluateFromYaml(string $values): bool
    {
        $parsed = Yaml::parse($values);
        assert(is_array($parsed));

        /** @var array<string, mixed> $parsed */
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
     * @return bool Returns true if the rule evaluation passes, false otherwise
     */
    public function evaluateFromYamlFile(string $values): bool
    {
        $contents = file_get_contents($values);
        assert(is_string($contents));

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
     * @return bool Returns true if the rule evaluation passes, false otherwise
     */
    public function evaluateFromRequest(Request $request): bool
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
     * @param array<string, mixed> $values      Data values used to resolve variable references
     *                                          in the rule definition during construction
     * @param array<string, mixed> $rule        Rule definition containing either a combinator
     *                                          with nested rules or an operator with field and value
     * @param RuleBuilder          $ruleBuilder Builder instance used to construct propositions
     *                                          and access variable values through array syntax
     *
     * @throws RuleEvaluatorException When the rule structure is invalid, the NOT
     *                                combinator has multiple values, or an unsupported
     *                                combinator is encountered
     *
     * @return Operator|Proposition Constructed operator or proposition representing
     *                              the rule definition logic
     */
    private static function proposition(array $values, array $rule, RuleBuilder $ruleBuilder): Operator|Proposition
    {
        // Handle combinator-based rules (and, or, xor, not)
        if (array_key_exists('combinator', $rule)) {
            assert(is_string($rule['combinator']));
            $method = 'logical'.ucfirst($rule['combinator']);

            // NOT combinator requires exactly one operand
            if ($rule['combinator'] === 'not') {
                assert(is_array($rule['value']));

                throw_if(count($rule['value']) !== 1, RuleEvaluatorException::invalidNotRule());

                assert(is_array($rule['value'][0]));

                /** @var array<string, mixed> $firstValue */
                $firstValue = $rule['value'][0];

                $result = $ruleBuilder->{$method}(
                    self::proposition($values, $firstValue, $ruleBuilder)
                );
                assert($result instanceof Operator || $result instanceof Proposition);

                return $result;
            }

            // Validate supported combinators
            throw_unless(in_array($rule['combinator'], ['and', 'or', 'xor'], true), RuleEvaluatorException::invalidCombinator($rule['combinator']));

            // Recursively process multiple sub-rules for and/or/xor
            assert(is_array($rule['value']));

            /** @var array<int, array<string, mixed>> $ruleValues */
            $ruleValues = $rule['value'];

            $result = $ruleBuilder->{$method}(
                ...array_map(
                    fn (array $subRule): Operator|Proposition => self::proposition($values, $subRule, $ruleBuilder),
                    $ruleValues,
                )
            );
            assert($result instanceof Operator || $result instanceof Proposition);

            return $result;
        }

        // Handle operator-based rules (comparison, arithmetic, etc.)
        if (array_key_exists('operator', $rule)) {
            // Resolve value: supports dot notation, direct variable reference, or literal
            $value = match (true) {
                is_string($rule['value'] ?? null) && str_contains($rule['value'], '.') => Arr::get($values, $rule['value']),
                is_string($rule['value'] ?? null) && array_key_exists($rule['value'], $values) => $ruleBuilder[$rule['value']],
                default => $rule['value'],
            };

            // Resolve field: supports dot notation for nested field access
            assert(array_key_exists('field', $rule));
            assert(is_string($rule['field']) || is_int($rule['field']));
            $fieldString = is_string($rule['field']) ? $rule['field'] : (string) $rule['field'];

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
                : $ruleBuilder[(string) $rule['field']];

            assert(is_string($rule['operator']));

            $result = $builder->{$rule['operator']}($value);
            assert($result instanceof Operator || $result instanceof Proposition);

            return $result;
        }

        throw RuleEvaluatorException::invalidRuleStructure();
    }
}
