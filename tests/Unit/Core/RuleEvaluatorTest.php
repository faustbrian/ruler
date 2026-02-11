<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\CompiledRuleCache;
use Cline\Ruler\Core\CompiledRuleKeyGenerator;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Core\RuleEvaluator;
use Cline\Ruler\Core\RuleEvaluatorCompilationResult;
use Cline\Ruler\Core\RuleEvaluatorReport;
use Cline\Ruler\Exceptions\RuleEvaluatorException;
use Illuminate\Http\Request;

describe('RuleEvaluator', function (): void {
    describe('Happy Paths', function (): void {
        test('evaluates simple rules successfully', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'combinator' => 'and',
                'value' => [
                    [
                        'field' => 'metrics.score',
                        'operator' => 'greaterThan',
                        'value' => 80,
                    ],
                    [
                        'field' => 'status',
                        'operator' => 'sameAs',
                        'value' => 'active',
                    ],
                ],
            ]);

            // Act
            $result = $evaluator->evaluateFromArray([
                'metrics' => ['score' => 90],
                'status' => 'active',
            ]);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('returns structured evaluation report', function (): void {
            $evaluator = RuleEvaluator::createFromArray([
                'combinator' => 'and',
                'value' => [
                    [
                        'field' => 'age',
                        'operator' => 'greaterThanOrEqualTo',
                        'value' => 18,
                    ],
                    [
                        'field' => 'status',
                        'operator' => 'sameAs',
                        'value' => 'active',
                    ],
                ],
            ]);

            $report = $evaluator->evaluateFromArray([
                'age' => 21,
                'status' => 'active',
            ]);

            expect($report)->toBeInstanceOf(RuleEvaluatorReport::class);
            expect($report->getResult())->toBeTrue();
            expect($report->getRuleResult()->matched)->toBeTrue();
            expect($report->getRuleResult()->actionExecuted)->toBeFalse();
            expect($report->getValues())->toBe([
                'age' => 21,
                'status' => 'active',
            ]);
        });

        test('creates evaluator from json string', function (): void {
            // Arrange
            $jsonRules = json_encode([
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ], \JSON_THROW_ON_ERROR);

            // Act
            $evaluator = RuleEvaluator::createFromJson($jsonRules);
            $result = $evaluator->evaluateFromArray(['status' => 'active']);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('compiles evaluator from array without throwing', function (): void {
            $result = RuleEvaluator::compileFromArray([
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ]);

            expect($result)->toBeInstanceOf(RuleEvaluatorCompilationResult::class);
            expect($result->isSuccess())->toBeTrue();
            expect($result->getEvaluator()->evaluateFromArray(['status' => 'active'])->getResult())
                ->toBeTrue();
        });

        test('compiles evaluator from json without throwing', function (): void {
            $jsonRules = json_encode([
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ], \JSON_THROW_ON_ERROR);

            $result = RuleEvaluator::compileFromJson($jsonRules);

            expect($result->isSuccess())->toBeTrue();
            expect($result->getEvaluator()->evaluateFromArray(['status' => 'active'])->getResult())
                ->toBeTrue();
        });

        test('creates evaluator from json file', function (): void {
            // Arrange
            $tempFile = tempnam(sys_get_temp_dir(), 'rule_test_');
            $rules = [
                'field' => 'age',
                'operator' => 'greaterThan',
                'value' => 18,
            ];
            file_put_contents($tempFile, json_encode($rules, \JSON_THROW_ON_ERROR));

            // Act
            $evaluator = RuleEvaluator::createFromJsonFile($tempFile);
            $result = $evaluator->evaluateFromArray(['age' => 25]);

            // Assert
            expect($result->getResult())->toBeTrue();

            // Cleanup
            unlink($tempFile);
        });

        test('creates evaluator from yaml string', function (): void {
            // Arrange
            $yamlRules = <<<'YAML'
field: status
operator: sameAs
value: active
YAML;

            // Act
            $evaluator = RuleEvaluator::createFromYaml($yamlRules);
            $result = $evaluator->evaluateFromArray(['status' => 'active']);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('creates evaluator from yaml file', function (): void {
            // Arrange
            $tempFile = tempnam(sys_get_temp_dir(), 'rule_test_yaml_');
            $yamlContent = <<<'YAML'
field: price
operator: lessThan
value: 100
YAML;
            file_put_contents($tempFile, $yamlContent);

            // Act
            $evaluator = RuleEvaluator::createFromYamlFile($tempFile);
            $result = $evaluator->evaluateFromArray(['price' => 50]);

            // Assert
            expect($result->getResult())->toBeTrue();

            // Cleanup
            unlink($tempFile);
        });

        test('evaluates rules from json string', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ]);
            $jsonValues = json_encode(['status' => 'active'], \JSON_THROW_ON_ERROR);

            // Act
            $result = $evaluator->evaluateFromJson($jsonValues);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('evaluates rules from json file', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'count',
                'operator' => 'greaterThan',
                'value' => 10,
            ]);
            $tempFile = tempnam(sys_get_temp_dir(), 'values_test_');
            file_put_contents($tempFile, json_encode(['count' => 15], \JSON_THROW_ON_ERROR));

            // Act
            $result = $evaluator->evaluateFromJsonFile($tempFile);

            // Assert
            expect($result->getResult())->toBeTrue();

            // Cleanup
            unlink($tempFile);
        });

        test('evaluates rules from yaml string', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'enabled',
                'operator' => 'sameAs',
                'value' => true,
            ]);
            $yamlValues = <<<'YAML'
enabled: true
YAML;

            // Act
            $result = $evaluator->evaluateFromYaml($yamlValues);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('evaluates rules from yaml file', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'level',
                'operator' => 'greaterThanOrEqualTo',
                'value' => 5,
            ]);
            $tempFile = tempnam(sys_get_temp_dir(), 'values_yaml_test_');
            $yamlContent = <<<'YAML'
level: 10
YAML;
            file_put_contents($tempFile, $yamlContent);

            // Act
            $result = $evaluator->evaluateFromYamlFile($tempFile);

            // Assert
            expect($result->getResult())->toBeTrue();

            // Cleanup
            unlink($tempFile);
        });

        test('evaluates rules from laravel request', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'user_id',
                'operator' => 'sameAs',
                'value' => 123,
            ]);
            $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST, ['user_id' => 123]);

            // Act
            $result = $evaluator->evaluateFromRequest($request);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('handles not combinator with exactly one operand', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'combinator' => 'not',
                'value' => [
                    [
                        'field' => 'status',
                        'operator' => 'sameAs',
                        'value' => 'inactive',
                    ],
                ],
            ]);

            // Act
            $result = $evaluator->evaluateFromArray(['status' => 'active']);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('evaluates complex nested rules with yaml', function (): void {
            // Arrange
            $yamlRules = <<<'YAML'
combinator: and
value:
  - field: age
    operator: greaterThan
    value: 18
  - field: country
    operator: sameAs
    value: US
YAML;
            $evaluator = RuleEvaluator::createFromYaml($yamlRules);

            // Act
            $result = $evaluator->evaluateFromArray([
                'age' => 25,
                'country' => 'US',
            ]);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('reuses compiled rules across evaluations with dynamic refs', function (): void {
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'amount',
                'operator' => 'greaterThan',
                'value' => '@threshold',
            ]);

            $first = $evaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 5,
            ]);

            $second = $evaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 20,
            ]);

            expect($first->getResult())->toBeTrue();
            expect($second->getResult())->toBeFalse();
        });

        test('uses isolated default cache per evaluator instance', function (): void {
            $definition = [
                'field' => 'amount',
                'operator' => 'greaterThan',
                'value' => 'threshold',
            ];

            $firstEvaluator = RuleEvaluator::createFromArray($definition);
            $secondEvaluator = RuleEvaluator::createFromArray($definition);

            $firstRun = $firstEvaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 5,
            ]);
            $secondRun = $firstEvaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 5,
            ]);
            $thirdRun = $secondEvaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 5,
            ]);

            expect($firstRun->getRuleResult()->ruleId)->toBe($secondRun->getRuleResult()->ruleId);
            expect($firstRun->getRuleResult()->ruleId)->toBe($thirdRun->getRuleResult()->ruleId);
        });

        test('resolves dot-notated value references at runtime', function (): void {
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'score',
                'operator' => 'greaterThanOrEqualTo',
                'value' => '@limits.minScore',
            ]);

            $first = $evaluator->evaluateFromArray([
                'score' => 90,
                'limits' => ['minScore' => 80],
            ]);

            $second = $evaluator->evaluateFromArray([
                'score' => 70,
                'limits' => ['minScore' => 80],
            ]);

            expect($first->getResult())->toBeTrue();
            expect($second->getResult())->toBeFalse();
        });

        test('treats plain string values as literals', function (): void {
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ]);

            $result = $evaluator->evaluateFromArray([
                'status' => 'active',
                'active' => 'inactive',
            ]);

            expect($result->getResult())->toBeTrue();
        });

        test('shares compiled rule cache across evaluator instances', function (): void {
            $cache = new class() implements CompiledRuleCache
            {
                public int $hits = 0;

                public int $misses = 0;

                public int $writes = 0;

                /** @var array<string, Rule> */
                private array $rules = [];

                public function get(string $key): ?Rule
                {
                    if (!isset($this->rules[$key])) {
                        ++$this->misses;

                        return null;
                    }

                    ++$this->hits;

                    return $this->rules[$key];
                }

                public function put(string $key, Rule $rule): void
                {
                    ++$this->writes;
                    $this->rules[$key] = $rule;
                }
            };

            $definition = [
                'field' => 'amount',
                'operator' => 'greaterThan',
                'value' => '@threshold',
            ];

            $firstEvaluator = RuleEvaluator::createFromArray($definition, $cache);
            $secondEvaluator = RuleEvaluator::createFromArray($definition, $cache);

            $firstResult = $firstEvaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 5,
            ]);

            $secondResult = $secondEvaluator->evaluateFromArray([
                'amount' => 10,
                'threshold' => 20,
            ]);

            expect($firstResult->getResult())->toBeTrue();
            expect($secondResult->getResult())->toBeFalse();
            expect($cache->writes)->toBe(1);
            expect($cache->misses)->toBe(1);
            expect($cache->hits)->toBe(3);
        });

        test('supports custom compiled-rule key generators', function (): void {
            $cache = new class() implements CompiledRuleCache
            {
                /** @var array<string> */
                public array $seenKeys = [];

                /** @var array<string, Rule> */
                private array $rules = [];

                public function get(string $key): ?Rule
                {
                    $this->seenKeys[] = $key;

                    return $this->rules[$key] ?? null;
                }

                public function put(string $key, Rule $rule): void
                {
                    $this->seenKeys[] = $key;
                    $this->rules[$key] = $rule;
                }
            };

            $keyGenerator = new class() implements CompiledRuleKeyGenerator
            {
                public function generate(array $rules): string
                {
                    return 'fixed-key';
                }
            };

            $evaluator = RuleEvaluator::createFromArray(
                [
                    'field' => 'status',
                    'operator' => 'sameAs',
                    'value' => 'active',
                ],
                $cache,
                $keyGenerator,
            );

            $result = $evaluator->evaluateFromArray(['status' => 'active']);

            expect($result->getResult())->toBeTrue();
            expect($cache->seenKeys)->each->toBe('fixed-key');
        });
    });

    describe('Sad Paths', function (): void {
        test('throws on invalid combinator during construction', function (): void {
            expect(fn (): RuleEvaluator => RuleEvaluator::createFromArray([
                'combinator' => 'nandd',
                'value' => [
                    [
                        'field' => 'status',
                        'operator' => 'sameAs',
                        'value' => 'active',
                    ],
                ],
            ]))->toThrow(RuleEvaluatorException::class);
        });

        test('returns structured compile failure for invalid combinator', function (): void {
            $result = RuleEvaluator::compileFromArray([
                'combinator' => 'nandd',
                'value' => [
                    [
                        'field' => 'status',
                        'operator' => 'sameAs',
                        'value' => 'active',
                    ],
                ],
            ]);

            expect($result->isSuccess())->toBeFalse();
            expect($result->getError())->toBeInstanceOf(RuleEvaluatorException::class);
            expect($result->getError()?->getErrorCode())->toBe('compile.invalid_combinator');
            expect($result->getError()?->getPhase())->toBe('compile');
            expect($result->getError()?->getPath())->toBe(['combinator']);
        });

        test('returns structured compile failure for invalid json payload', function (): void {
            $result = RuleEvaluator::compileFromJson('{invalid json');

            expect($result->isSuccess())->toBeFalse();
            expect($result->getError())->toBeInstanceOf(RuleEvaluatorException::class);
            expect($result->getError()?->getErrorCode())->toBe('compile.invalid_rule_structure');
            expect($result->getError()?->getPhase())->toBe('compile');
            expect($result->getError()?->getPath())->toBe(['rules']);
            expect($result->getError()?->getDetails()['format'] ?? null)->toBe('json');
        });

        test('returns structured compile error payload for invalid combinator', function (): void {
            try {
                RuleEvaluator::createFromArray([
                    'combinator' => 'nandd',
                    'value' => [
                        [
                            'field' => 'status',
                            'operator' => 'sameAs',
                            'value' => 'active',
                        ],
                    ],
                ]);
                test()->fail('Expected RuleEvaluatorException was not thrown.');
            } catch (RuleEvaluatorException $ruleEvaluatorException) {
                expect($ruleEvaluatorException->getErrorCode())->toBe('compile.invalid_combinator');
                expect($ruleEvaluatorException->getPhase())->toBe('compile');
                expect($ruleEvaluatorException->getPath())->toBe(['combinator']);
                expect($ruleEvaluatorException->getDetails())->toBe(['combinator' => 'nandd']);
            }
        });

        test('returns structured compile error payload for unknown operator', function (): void {
            try {
                RuleEvaluator::createFromArray([
                    'field' => 'status',
                    'operator' => 'unknownOperator',
                    'value' => 'active',
                ]);
                test()->fail('Expected RuleEvaluatorException was not thrown.');
            } catch (RuleEvaluatorException $ruleEvaluatorException) {
                expect($ruleEvaluatorException->getErrorCode())->toBe('compile.unknown_operator');
                expect($ruleEvaluatorException->getPhase())->toBe('compile');
                expect($ruleEvaluatorException->getPath())->toBe(['operator']);
                expect($ruleEvaluatorException->getDetails())->toBe([
                    'operator' => 'unknownOperator',
                    'field' => 'status',
                ]);
            }
        });

        test('returns false when propositions fail', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'metrics.score',
                'operator' => 'greaterThan',
                'value' => 80,
            ]);

            // Act
            $result = $evaluator->evaluateFromArray([
                'metrics' => ['score' => 40],
            ]);

            // Assert
            expect($result->getResult())->toBeFalse();
        });

        test('throws exception when not combinator has multiple operands', function (): void {
            expect(fn (): RuleEvaluator => RuleEvaluator::createFromArray([
                'combinator' => 'not',
                'value' => [
                    [
                        'field' => 'status',
                        'operator' => 'sameAs',
                        'value' => 'active',
                    ],
                    [
                        'field' => 'enabled',
                        'operator' => 'sameAs',
                        'value' => true,
                    ],
                ],
            ]))->toThrow(RuleEvaluatorException::class);
        });

        test('throws exception when not combinator has no operands', function (): void {
            expect(fn (): RuleEvaluator => RuleEvaluator::createFromArray([
                'combinator' => 'not',
                'value' => [],
            ]))->toThrow(RuleEvaluatorException::class);
        });

        test('throws exception for invalid rule structure without combinator or operator', function (): void {
            expect(fn (): RuleEvaluator => RuleEvaluator::createFromArray([
                'field' => 'status',
                'value' => 'active',
                // Missing 'operator' key
            ]))->toThrow(RuleEvaluatorException::class);
        });

        test('returns false when json evaluation fails validation', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'score',
                'operator' => 'greaterThan',
                'value' => 100,
            ]);
            $jsonValues = json_encode(['score' => 50], \JSON_THROW_ON_ERROR);

            // Act
            $result = $evaluator->evaluateFromJson($jsonValues);

            // Assert
            expect($result->getResult())->toBeFalse();
        });

        test('returns false when yaml evaluation fails validation', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'authorized',
                'operator' => 'sameAs',
                'value' => true,
            ]);
            $yamlValues = <<<'YAML'
authorized: false
YAML;

            // Act
            $result = $evaluator->evaluateFromYaml($yamlValues);

            // Assert
            expect($result->getResult())->toBeFalse();
        });

        test('returns false when request evaluation fails validation', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'role',
                'operator' => 'sameAs',
                'value' => 'admin',
            ]);
            $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST, ['role' => 'user']);

            // Act
            $result = $evaluator->evaluateFromRequest($request);

            // Assert
            expect($result->getResult())->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        test('handles empty request data', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'optional',
                'operator' => 'sameAs',
                'value' => null,
            ]);
            $request = Request::create('/test', Symfony\Component\HttpFoundation\Request::METHOD_POST, []);

            // Act
            $result = $evaluator->evaluateFromRequest($request);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('handles json with nested arrays', function (): void {
            // Arrange
            $jsonRules = json_encode([
                'combinator' => 'or',
                'value' => [
                    [
                        'field' => 'metadata.tags',
                        'operator' => 'setContains',
                        'value' => 'urgent',
                    ],
                    [
                        'field' => 'priority',
                        'operator' => 'greaterThan',
                        'value' => 5,
                    ],
                ],
            ], \JSON_THROW_ON_ERROR);
            $evaluator = RuleEvaluator::createFromJson($jsonRules);

            // Act
            $result = $evaluator->evaluateFromArray([
                'metadata' => ['tags' => ['urgent', 'review']],
                'priority' => 3,
            ]);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('handles yaml with special characters', function (): void {
            // Arrange
            $yamlRules = <<<'YAML'
field: description
operator: stringContains
value: "Hello, World!"
YAML;
            $evaluator = RuleEvaluator::createFromYaml($yamlRules);

            // Act
            $result = $evaluator->evaluateFromArray([
                'description' => 'Say Hello, World! to everyone',
            ]);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('handles deeply nested dot notation in json file', function (): void {
            // Arrange
            $tempFile = tempnam(sys_get_temp_dir(), 'deep_rule_test_');
            $rules = [
                'field' => 'data.user.profile.verified',
                'operator' => 'sameAs',
                'value' => true,
            ];
            file_put_contents($tempFile, json_encode($rules, \JSON_THROW_ON_ERROR));
            $evaluator = RuleEvaluator::createFromJsonFile($tempFile);

            // Act
            $result = $evaluator->evaluateFromArray([
                'data' => [
                    'user' => [
                        'profile' => [
                            'verified' => true,
                        ],
                    ],
                ],
            ]);

            // Assert
            expect($result->getResult())->toBeTrue();

            // Cleanup
            unlink($tempFile);
        });

        test('handles unicode characters in yaml values', function (): void {
            // Arrange
            $yamlValues = <<<'YAML'
name: "José García"
city: "São Paulo"
YAML;
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'name',
                'operator' => 'sameAs',
                'value' => 'José García',
            ]);

            // Act
            $result = $evaluator->evaluateFromYaml($yamlValues);

            // Assert
            expect($result->getResult())->toBeTrue();
        });

        test('handles request with query parameters and body data', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'combinator' => 'and',
                'value' => [
                    [
                        'field' => 'type',
                        'operator' => 'sameAs',
                        'value' => 'premium',
                    ],
                    [
                        'field' => 'verified',
                        'operator' => 'sameAs',
                        'value' => true,
                    ],
                ],
            ]);
            $request = Request::create(
                '/test?type=premium',
                Symfony\Component\HttpFoundation\Request::METHOD_POST,
                ['verified' => true],
            );

            // Act
            $result = $evaluator->evaluateFromRequest($request);

            // Assert
            expect($result->getResult())->toBeTrue();
        });
    });
});
