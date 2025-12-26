<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Wirefilter\WirefilterParser;

describe('WirefilterParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple comparison expression', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('age > 18');

            $context = new Context(['age' => 25]);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse comparison with field that fails', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('age > 18');

            $context = new Context(['age' => 15]);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse equality operator', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('status == "active"');

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical and expression', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('age >= 18 and country == "US"');

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse logical or expression', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('age >= 21 or country == "US"');

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse expression with parentheses', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('(age >= 18 and country == "US") or age >= 21');

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse mathematical expression', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('price + shipping > 100');

            $trueContext = new Context(['price' => 80, 'shipping' => 25]);
            $falseContext = new Context(['price' => 50, 'shipping' => 10]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse not expression', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('not (age < 18)');

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new WirefilterParser();
            $executed = false;
            $rule = $parser->parseWithAction('age >= 18', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new WirefilterParser();
            $executed = false;
            $rule = $parser->parseWithAction('age >= 18', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parse in operator with array', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('country in ["US", "CA", "UK"]');

            $trueContext = new Context(['country' => 'US']);
            $falseContext = new Context(['country' => 'FR']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse matches operator with regex', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('phone matches "/^\\\\d{3}-\\\\d{4}$/"');

            $trueContext = new Context(['phone' => '123-4567']);
            $falseContext = new Context(['phone' => '12-34567']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse strict equality operator', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('age === 18');

            $trueContext = new Context(['age' => 18]);
            $falseContext = new Context(['age' => '18']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse strict inequality operator', function (): void {
            $parser = new WirefilterParser();
            $rule = $parser->parse('verified !== false');

            $trueContext = new Context(['verified' => 0]);
            $falseContext = new Context(['verified' => false]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });
    });
});
