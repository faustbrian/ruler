<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\RuleEvaluator;
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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();

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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();

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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();

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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();

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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();
        });
    });

    describe('Sad Paths', function (): void {
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
            expect($result)->toBeFalse();
        });

        test('throws exception when not combinator has multiple operands', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
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
            ]);

            // Act & Assert
            expect(fn (): bool => $evaluator->evaluateFromArray(['status' => 'active', 'enabled' => true]))
                ->toThrow(RuleEvaluatorException::class);
        });

        test('throws exception when not combinator has no operands', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'combinator' => 'not',
                'value' => [],
            ]);

            // Act & Assert
            expect(fn (): bool => $evaluator->evaluateFromArray([]))
                ->toThrow(RuleEvaluatorException::class);
        });

        test('throws exception for invalid rule structure without combinator or operator', function (): void {
            // Arrange
            $evaluator = RuleEvaluator::createFromArray([
                'field' => 'status',
                'value' => 'active',
                // Missing 'operator' key
            ]);

            // Act & Assert
            expect(fn (): bool => $evaluator->evaluateFromArray(['status' => 'active']))
                ->toThrow(RuleEvaluatorException::class);
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
            expect($result)->toBeFalse();
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
            expect($result)->toBeFalse();
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
            expect($result)->toBeFalse();
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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();

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
            expect($result)->toBeTrue();
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
            expect($result)->toBeTrue();
        });
    });
});
