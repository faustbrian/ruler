<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\CanonicalJsonCompiledRuleKeyGenerator;

describe('CanonicalJsonCompiledRuleKeyGenerator', function (): void {
    test('generates identical keys for associative key-order variants', function (): void {
        $generator = new CanonicalJsonCompiledRuleKeyGenerator();

        $first = [
            'operator' => 'sameAs',
            'field' => 'status',
            'value' => 'active',
        ];

        $second = [
            'value' => 'active',
            'field' => 'status',
            'operator' => 'sameAs',
        ];

        expect($generator->generate($first))->toBe($generator->generate($second));
    });

    test('preserves list ordering while generating keys', function (): void {
        $generator = new CanonicalJsonCompiledRuleKeyGenerator();

        $first = [
            'combinator' => 'and',
            'value' => [
                ['field' => 'a', 'operator' => 'sameAs', 'value' => true],
                ['field' => 'b', 'operator' => 'sameAs', 'value' => true],
            ],
        ];

        $second = [
            'combinator' => 'and',
            'value' => [
                ['field' => 'b', 'operator' => 'sameAs', 'value' => true],
                ['field' => 'a', 'operator' => 'sameAs', 'value' => true],
            ],
        ];

        expect($generator->generate($first))->not->toBe($generator->generate($second));
    });
});
