<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
});
