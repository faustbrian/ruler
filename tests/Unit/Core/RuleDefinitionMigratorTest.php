<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\CompatibilityMode;
use Cline\Ruler\Core\RuleDefinitionMigrator;

describe('RuleDefinitionMigrator', function (): void {
    test('migrates legacy dotted value reference in comparison rule', function (): void {
        $migrated = RuleDefinitionMigrator::migrateLegacyStringReferences([
            'field' => 'score',
            'operator' => 'greaterThan',
            'value' => 'limits.minScore',
        ]);

        expect($migrated['value'])->toBe('@limits.minScore');
    });

    test('does not change explicit reference syntax', function (): void {
        $migrated = RuleDefinitionMigrator::migrateLegacyStringReferences([
            'field' => 'score',
            'operator' => 'greaterThan',
            'value' => '@limits.minScore',
        ]);

        expect($migrated['value'])->toBe('@limits.minScore');
    });

    test('does not change plain literal strings', function (): void {
        $migrated = RuleDefinitionMigrator::migrateLegacyStringReferences([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ]);

        expect($migrated['value'])->toBe('active');
    });

    test('recursively migrates nested combinator operands', function (): void {
        $migrated = RuleDefinitionMigrator::migrateLegacyStringReferences([
            'combinator' => 'and',
            'value' => [
                [
                    'field' => 'score',
                    'operator' => 'greaterThan',
                    'value' => 'limits.minScore',
                ],
                [
                    'combinator' => 'or',
                    'value' => [
                        [
                            'field' => 'age',
                            'operator' => 'greaterThanOrEqualTo',
                            'value' => 'thresholds.minAge',
                        ],
                        [
                            'field' => 'status',
                            'operator' => 'sameAs',
                            'value' => 'active',
                        ],
                    ],
                ],
            ],
        ]);

        expect($migrated['value'][0]['value'])->toBe('@limits.minScore');
        expect($migrated['value'][1]['value'][0]['value'])->toBe('@thresholds.minAge');
        expect($migrated['value'][1]['value'][1]['value'])->toBe('active');
    });

    test('migrates legacy logical group syntax to ast combinators', function (): void {
        $migrated = RuleDefinitionMigrator::migrateLegacyLogicalCombinators([
            'type' => 'logicalAnd',
            'rules' => [
                [
                    'field' => 'status',
                    'operator' => 'sameAs',
                    'value' => 'active',
                ],
                [
                    'type' => 'logicalOr',
                    'rules' => [
                        [
                            'field' => 'score',
                            'operator' => 'greaterThan',
                            'value' => 80,
                        ],
                    ],
                ],
            ],
        ]);

        expect($migrated)->toBe([
            'combinator' => 'and',
            'value' => [
                [
                    'field' => 'status',
                    'operator' => 'sameAs',
                    'value' => 'active',
                ],
                [
                    'combinator' => 'or',
                    'value' => [
                        [
                            'field' => 'score',
                            'operator' => 'greaterThan',
                            'value' => 80,
                        ],
                    ],
                ],
            ],
        ]);
    });

    test('applies legacy compatibility migration end-to-end', function (): void {
        $migrated = RuleDefinitionMigrator::migrateForCompatibilityMode([
            'type' => 'logicalAnd',
            'rules' => [
                [
                    'field' => 'score',
                    'operator' => 'greaterThanOrEqualTo',
                    'value' => 'limits.minScore',
                ],
            ],
        ], CompatibilityMode::Legacy);

        expect($migrated)->toBe([
            'combinator' => 'and',
            'value' => [
                [
                    'field' => 'score',
                    'operator' => 'greaterThanOrEqualTo',
                    'value' => '@limits.minScore',
                ],
            ],
        ]);
    });

    test('migrates legacy operator aliases recursively', function (): void {
        $migrated = RuleDefinitionMigrator::migrateForCompatibilityMode([
            'combinator' => 'and',
            'value' => [
                [
                    'field' => 'serviceId',
                    'operator' => 'contains',
                    'value' => 'post',
                ],
                [
                    'field' => 'country',
                    'operator' => 'in',
                    'value' => ['SE', 'FI'],
                ],
                [
                    'combinator' => 'or',
                    'value' => [
                        [
                            'field' => 'status',
                            'operator' => 'doesNotContain',
                            'value' => 'inactive',
                        ],
                        [
                            'field' => 'category',
                            'operator' => 'notIn',
                            'value' => ['banned'],
                        ],
                    ],
                ],
            ],
        ], CompatibilityMode::Legacy);

        expect($migrated['value'][0]['operator'])->toBe('stringContains');
        expect($migrated['value'][1]['operator'])->toBe('setContains');
        expect($migrated['value'][2]['value'][0]['operator'])->toBe('stringDoesNotContain');
        expect($migrated['value'][2]['value'][1]['operator'])->toBe('setDoesNotContain');
    });

    test('does not change rules in strict compatibility mode', function (): void {
        $legacy = [
            'type' => 'logicalAnd',
            'rules' => [
                [
                    'field' => 'score',
                    'operator' => 'greaterThanOrEqualTo',
                    'value' => 'limits.minScore',
                ],
            ],
        ];

        expect(RuleDefinitionMigrator::migrateForCompatibilityMode($legacy, CompatibilityMode::Strict))
            ->toBe($legacy);
    });
});
