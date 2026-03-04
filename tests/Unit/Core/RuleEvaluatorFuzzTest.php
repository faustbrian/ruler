<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\RuleEvaluator;

/**
 * @return array<string, mixed>
 */
function randomValidRuleDefinition(int $depth = 0): array
{
    $fields = ['age', 'score', 'status', 'metrics.total', 'limits.minScore'];
    $operators = ['sameAs', 'greaterThan', 'lessThanOrEqualTo'];
    $literals = [0, 5, 18, 42, 75, 100, 'active', 'inactive', '@limits.minScore'];

    if ($depth >= 3 || mt_rand(0, 1) === 0) {
        /** @var mixed $literal */
        $literal = $literals[array_rand($literals)];

        return [
            'field' => $fields[array_rand($fields)],
            'operator' => $operators[array_rand($operators)],
            'value' => $literal,
        ];
    }

    $combinator = ['and', 'or', 'not'][array_rand(['and', 'or', 'not'])];
    $operandCount = $combinator === 'not' ? 1 : mt_rand(1, 3);

    $operands = [];

    for ($index = 0; $index < $operandCount; ++$index) {
        $operands[] = randomValidRuleDefinition($depth + 1);
    }

    return [
        'combinator' => $combinator,
        'value' => $operands,
    ];
}

/**
 * @return array<string, mixed>
 */
function randomContextValues(): array
{
    return [
        'age' => mt_rand(0, 100),
        'score' => mt_rand(0, 100),
        'status' => mt_rand(0, 1) === 1 ? 'active' : 'inactive',
        'metrics' => ['total' => mt_rand(0, 100)],
        'limits' => ['minScore' => mt_rand(0, 100)],
    ];
}

/**
 * @return array<string, mixed>
 */
function randomMalformedRuleDefinition(): array
{
    $cases = [
        [
            'field' => 'status',
            'value' => 'active',
        ],
        [
            'combinator' => 'not',
            'value' => [],
        ],
        [
            'combinator' => 'invalid',
            'value' => [['field' => 'age', 'operator' => 'greaterThan', 'value' => 18]],
        ],
        [
            'field' => 'status',
            'operator' => ['sameAs'],
            'value' => 'active',
        ],
        [
            'combinator' => 'and',
            'value' => 'not-an-array',
        ],
    ];

    return $cases[array_rand($cases)];
}

describe('RuleEvaluator Fuzz', function (): void {
    test('compiles and evaluates generated valid rule trees', function (): void {
        mt_srand(1_337);

        for ($iteration = 0; $iteration < 100; ++$iteration) {
            $rule = randomValidRuleDefinition();
            $context = randomContextValues();

            $result = RuleEvaluator::compileFromArray($rule);
            expect($result->isSuccess())->toBeTrue(sprintf('compile failed at iteration %d', $iteration));

            $evaluation = $result->getEvaluator()->evaluateFromArray($context);
            expect(is_bool($evaluation->getResult()))->toBeTrue();
        }
    });

    test('returns structured failure for malformed generated payloads', function (): void {
        mt_srand(7_331);

        for ($iteration = 0; $iteration < 100; ++$iteration) {
            $malformed = randomMalformedRuleDefinition();
            $result = RuleEvaluator::compileFromArray($malformed);

            expect($result->isSuccess())->toBeFalse();
            expect($result->getError())->not->toBeNull();
            expect(count($result->getError()?->toArray() ?? []))->toBeGreaterThan(0);
        }
    });
});
