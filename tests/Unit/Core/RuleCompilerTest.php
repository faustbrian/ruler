<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\CompatibilityMode;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\RuleCompiler;
use Cline\Ruler\Core\RuleId;
use Cline\Ruler\Core\RuleIds;
use Cline\Ruler\Enums\RuleErrorCode;

describe('RuleCompiler', function (): void {
    test('compiles rule from array and evaluates', function (): void {
        $result = RuleCompiler::compileFromArray([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ]);

        expect($result->isSuccess())->toBeTrue();
        expect($result->getRule()->getRuleId())
            ->toEqual(RuleIds::fromDefinition([
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ]));

        expect($result->getRule()->evaluate(
            new Context([
                'status' => 'active',
            ]),
        ))->toBeTrue();
    });

    test('compiles rule from array with explicit id', function (): void {
        $result = RuleCompiler::compileFromArray(
            [
                'field' => 'status',
                'operator' => 'sameAs',
                'value' => 'active',
            ],
            RuleId::fromString('my-explicit-id'),
        );

        expect($result->isSuccess())->toBeTrue();
        expect($result->getRule()->getId())->toBe('my-explicit-id');
    });

    test('compiles rule from json and yaml payloads', function (): void {
        $json = json_encode([
            'field' => 'score',
            'operator' => 'greaterThan',
            'value' => 50,
        ], \JSON_THROW_ON_ERROR);

        $yaml = <<<'YAML'
field: score
operator: greaterThan
value: 50
YAML;

        $jsonResult = RuleCompiler::compileFromJson($json);
        $yamlResult = RuleCompiler::compileFromYaml($yaml);

        expect($jsonResult->isSuccess())->toBeTrue();
        expect($yamlResult->isSuccess())->toBeTrue();

        $context = new Context(['score' => 75]);
        expect($jsonResult->getRule()->evaluate($context))->toBeTrue();
        expect($yamlResult->getRule()->evaluate($context))->toBeTrue();
    });

    test('compiles rule from json and yaml files', function (): void {
        $jsonFile = tempnam(sys_get_temp_dir(), 'rule_compiler_json_');
        $yamlFile = tempnam(sys_get_temp_dir(), 'rule_compiler_yaml_');

        file_put_contents($jsonFile, json_encode([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ], \JSON_THROW_ON_ERROR));

        file_put_contents($yamlFile, <<<'YAML'
field: status
operator: sameAs
value: active
YAML);

        $jsonResult = RuleCompiler::compileFromJsonFile($jsonFile);
        $yamlResult = RuleCompiler::compileFromYamlFile($yamlFile);

        unlink($jsonFile);
        unlink($yamlFile);

        expect($jsonResult->isSuccess())->toBeTrue();
        expect($yamlResult->isSuccess())->toBeTrue();
    });

    test('returns structured failures for malformed payloads', function (): void {
        $arrayResult = RuleCompiler::compileFromArray([
            'field' => 'status',
            'value' => 'active',
        ]);

        $jsonResult = RuleCompiler::compileFromJson('{');
        $yamlResult = RuleCompiler::compileFromYaml(': not-valid');

        expect($arrayResult->isSuccess())->toBeFalse();
        expect($jsonResult->isSuccess())->toBeFalse();
        expect($yamlResult->isSuccess())->toBeFalse();

        expect($arrayResult->getError()?->getErrorCode())
            ->toBe(RuleErrorCode::CompileInvalidRuleStructure);
        expect($jsonResult->getError()?->getErrorCode())
            ->toBe(RuleErrorCode::CompileInvalidRuleStructure);
        expect($yamlResult->getError()?->getErrorCode())
            ->toBe(RuleErrorCode::CompileInvalidRuleStructure);
    });

    test('supports context references via @ syntax', function (): void {
        $result = RuleCompiler::compileFromArray([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => '@expected.status',
        ]);

        expect($result->isSuccess())->toBeTrue();
        expect($result->getRule()->evaluate(
            new Context([
                'status' => 'active',
                'expected' => ['status' => 'active'],
            ]),
        ))->toBeTrue();
    });

    test('supports legacy payloads in compatibility legacy mode', function (): void {
        $result = RuleCompiler::compileFromArray(
            [
                'type' => 'logicalAnd',
                'rules' => [
                    [
                        'field' => 'score',
                        'operator' => 'greaterThanOrEqualTo',
                        'value' => 'limits.minScore',
                    ],
                ],
            ],
            compatibilityMode: CompatibilityMode::Legacy,
        );

        expect($result->isSuccess())->toBeTrue();
        expect($result->getRule()->evaluate(
            new Context([
                'score' => 75,
                'limits' => ['minScore' => 50],
            ]),
        ))->toBeTrue();
    });

    test('supports legacy json payloads in compatibility legacy mode', function (): void {
        $json = json_encode([
            'type' => 'logicalAnd',
            'rules' => [
                [
                    'field' => 'score',
                    'operator' => 'greaterThanOrEqualTo',
                    'value' => 'limits.minScore',
                ],
            ],
        ], \JSON_THROW_ON_ERROR);

        $result = RuleCompiler::compileFromJson(
            $json,
            compatibilityMode: CompatibilityMode::Legacy,
        );

        expect($result->isSuccess())->toBeTrue();
        expect($result->getRule()->evaluate(
            new Context([
                'score' => 90,
                'limits' => ['minScore' => 50],
            ]),
        ))->toBeTrue();
    });

    test('rejects legacy payloads in strict mode', function (): void {
        $result = RuleCompiler::compileFromArray([
            'type' => 'logicalAnd',
            'rules' => [
                [
                    'field' => 'score',
                    'operator' => 'greaterThanOrEqualTo',
                    'value' => 'limits.minScore',
                ],
            ],
        ]);

        expect($result->isSuccess())->toBeFalse();
    });
});
