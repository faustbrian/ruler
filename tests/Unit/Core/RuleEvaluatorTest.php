<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\RuleEvaluator;

it('evaluates simple rules successfully', function (): void {
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

    expect($evaluator->evaluateFromArray([
        'metrics' => ['score' => 90],
        'status' => 'active',
    ]))->toBeTrue();
});

it('returns false when propositions fail', function (): void {
    $evaluator = RuleEvaluator::createFromArray([
        'field' => 'metrics.score',
        'operator' => 'greaterThan',
        'value' => 80,
    ]);

    expect($evaluator->evaluateFromArray([
        'metrics' => ['score' => 40],
    ]))->toBeFalse();
});
