<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\RuleEvaluator;
use Cline\Ruler\Enums\RuleErrorCode;

describe('Rule Schema Compatibility', function (): void {
    test('ships versioned v1 schema with expected metadata', function (): void {
        $raw = file_get_contents(__DIR__.'/../../../schemas/rule-definition.v1.schema.json');
        expect($raw)->toBeString();

        $schema = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        expect($schema)->toBeArray();

        expect($schema['$schema'] ?? null)->toBe('https://json-schema.org/draft/2020-12/schema');
        expect($schema['$id'] ?? null)->toBe('https://cline.sh/schemas/ruler/rule-definition.v1.schema.json');
        expect($schema['title'] ?? null)->toBe('Ruler Rule Definition v1');
        expect($schema['$defs']['combinatorRule']['properties']['combinator']['enum'] ?? [])->toContain('and');
        expect($schema['$defs']['combinatorRule']['properties']['combinator']['enum'] ?? [])->toContain('not');
    });

    test('v1 valid fixtures compile successfully', function (string $fixture): void {
        $raw = file_get_contents($fixture);
        expect($raw)->toBeString();

        $result = RuleEvaluator::compileFromJson($raw);

        expect($result->isSuccess())->toBeTrue(sprintf('Fixture failed: %s', $fixture));
    })->with([
        __DIR__.'/../../Fixtures/Rules/v1/valid/simple-comparison.json',
        __DIR__.'/../../Fixtures/Rules/v1/valid/nested-combinator.json',
        __DIR__.'/../../Fixtures/Rules/v1/valid/context-reference.json',
    ]);

    test('v1 invalid fixtures fail with stable error codes', function (string $fixture, RuleErrorCode $expected): void {
        $raw = file_get_contents($fixture);
        expect($raw)->toBeString();

        $result = RuleEvaluator::compileFromJson($raw);

        expect($result->isSuccess())->toBeFalse();
        expect($result->getError())->not->toBeNull();
        expect($result->getError()?->getErrorCode())->toBe($expected);
    })->with([
        [
            __DIR__.'/../../Fixtures/Rules/v1/invalid/missing-operator.json',
            RuleErrorCode::CompileInvalidRuleStructure,
        ],
        [
            __DIR__.'/../../Fixtures/Rules/v1/invalid/not-multiple-operands.json',
            RuleErrorCode::CompileInvalidNotArity,
        ],
        [
            __DIR__.'/../../Fixtures/Rules/v1/invalid/unknown-combinator.json',
            RuleErrorCode::CompileInvalidCombinator,
        ],
    ]);
});
