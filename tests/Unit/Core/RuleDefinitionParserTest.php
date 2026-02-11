<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Definition\CombinatorRuleDefinition;
use Cline\Ruler\Core\Definition\ComparisonRuleDefinition;
use Cline\Ruler\Core\Definition\RuleCombinator;
use Cline\Ruler\Core\Definition\RuleDefinitionParser;
use Cline\Ruler\Exceptions\RuleEvaluatorException;

describe('RuleDefinitionParser', function (): void {
    test('parses operator rules into typed definitions', function (): void {
        $definition = RuleDefinitionParser::fromArray([
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ]);

        expect($definition)->toBeInstanceOf(ComparisonRuleDefinition::class);
        expect($definition->field)->toBe('status');
        expect($definition->operator)->toBe('sameAs');
        expect($definition->value)->toBe('active');
    });

    test('parses combinator rules into typed definitions', function (): void {
        $definition = RuleDefinitionParser::fromArray([
            'combinator' => 'and',
            'value' => [
                [
                    'field' => 'status',
                    'operator' => 'sameAs',
                    'value' => 'active',
                ],
            ],
        ]);

        expect($definition)->toBeInstanceOf(CombinatorRuleDefinition::class);
        expect($definition->combinator)->toBe(RuleCombinator::And);
        expect($definition->operands)->toHaveCount(1);
        expect($definition->operands[0])->toBeInstanceOf(ComparisonRuleDefinition::class);
    });

    test('rejects invalid combinators', function (): void {
        expect(fn (): CombinatorRuleDefinition => RuleDefinitionParser::fromArray([
            'combinator' => 'invalid',
            'value' => [],
        ]))->toThrow(RuleEvaluatorException::class);
    });
});
