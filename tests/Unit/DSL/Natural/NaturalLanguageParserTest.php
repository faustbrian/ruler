<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Natural\NaturalLanguageParser;

describe('NaturalLanguageParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple comparison expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is greater than 18');

            $context = new Context(['age' => 25]);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse comparison that fails', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is greater than 18');

            $context = new Context(['age' => 15]);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse equality operator', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('status equals active');

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse is operator', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('status is active');

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse is not operator', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('status is not inactive');

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse greater than or equal to (using is at least)', function (): void {
            $parser = new NaturalLanguageParser();
            // Note: "is at least" is preferred over "is greater than or equal to"
            // to avoid confusion with logical "or" operator
            $rule = $parser->parse('age is at least 18');

            $trueContext1 = new Context(['age' => 18]);
            $trueContext2 = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 17]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse is at least', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is at least 18');

            $trueContext = new Context(['age' => 18]);
            $falseContext = new Context(['age' => 17]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is less than 18');

            $trueContext = new Context(['age' => 15]);
            $falseContext = new Context(['age' => 25]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than or equal to (using is at most)', function (): void {
            $parser = new NaturalLanguageParser();
            // Note: "is at most" is preferred over "is less than or equal to"
            // to avoid confusion with logical "or" operator
            $rule = $parser->parse('age is at most 18');

            $trueContext1 = new Context(['age' => 18]);
            $trueContext2 = new Context(['age' => 15]);
            $falseContext = new Context(['age' => 25]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical and expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is at least 18 and country equals US');

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse logical or expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is at least 21 or country equals US');

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse between range expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('age is between 18 and 65');

            $trueContext1 = new Context(['age' => 18]);
            $trueContext2 = new Context(['age' => 30]);
            $trueContext3 = new Context(['age' => 65]);
            $falseContext1 = new Context(['age' => 17]);
            $falseContext2 = new Context(['age' => 66]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($trueContext3))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse is one of expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('country is one of US, CA, UK');

            $trueContext1 = new Context(['country' => 'US']);
            $trueContext2 = new Context(['country' => 'CA']);
            $falseContext = new Context(['country' => 'FR']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse is either or expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('status is either active or pending');

            $trueContext1 = new Context(['status' => 'active']);
            $trueContext2 = new Context(['status' => 'pending']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse is not one of expression', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('country is not one of US, CA');

            $trueContext = new Context(['country' => 'FR']);
            $falseContext = new Context(['country' => 'US']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse contains string operation', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('name contains John');

            $trueContext = new Context(['name' => 'John Doe']);
            $falseContext = new Context(['name' => 'Jane Doe']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse starts with string operation', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('email starts with admin');

            $trueContext = new Context(['email' => 'admin@example.com']);
            $falseContext = new Context(['email' => 'user@example.com']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse ends with string operation', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('filename ends with .pdf');

            $trueContext = new Context(['filename' => 'document.pdf']);
            $falseContext = new Context(['filename' => 'document.txt']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse expression with parentheses', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('(age is at least 18 and country equals US) or age is at least 21');

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new NaturalLanguageParser();
            $executed = false;
            $rule = $parser->parseWithAction('age is at least 18', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new NaturalLanguageParser();
            $executed = false;
            $rule = $parser->parseWithAction('age is at least 18', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parse boolean values', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('active is true');

            $trueContext = new Context(['active' => true]);
            $falseContext = new Context(['active' => false]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse numeric values', function (): void {
            $parser = new NaturalLanguageParser();
            $rule = $parser->parse('score is 100');

            $trueContext = new Context(['score' => 100]);
            $falseContext = new Context(['score' => 50]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        test('throws exception for invalid expression', function (): void {
            $parser = new NaturalLanguageParser();

            expect(fn (): Rule => $parser->parse('age invalid operator 18'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for empty expression', function (): void {
            $parser = new NaturalLanguageParser();

            expect(fn (): Rule => $parser->parse(''))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
