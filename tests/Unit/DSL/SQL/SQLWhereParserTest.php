<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\SQL\SQLWhereParser;

describe('SQLWhereParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple comparison expression', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('age > 18');

            $context = new Context(['age' => 25]);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse comparison with field that fails', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('age > 18');

            $context = new Context(['age' => 15]);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse equality operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("status = 'active'");

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse inequality operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("status != 'inactive'");

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical AND expression', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("age >= 18 AND country = 'US'");

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse logical OR expression', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("age >= 21 OR country = 'US'");

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse expression with parentheses', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("(age >= 18 AND country = 'US') OR age >= 21");

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse NOT expression', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('NOT (age < 18)');

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new SQLWhereParser();
            $executed = false;
            $rule = $parser->parseWithAction('age >= 18', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new SQLWhereParser();
            $executed = false;
            $rule = $parser->parseWithAction('age >= 18', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parse IN operator with array', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("country IN ('US', 'CA', 'UK')");

            $trueContext = new Context(['country' => 'US']);
            $falseContext = new Context(['country' => 'FR']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse NOT IN operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("country NOT IN ('FR', 'DE')");

            $trueContext = new Context(['country' => 'US']);
            $falseContext = new Context(['country' => 'FR']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse BETWEEN operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('age BETWEEN 18 AND 65');

            $trueContext = new Context(['age' => 30]);
            $falseContext1 = new Context(['age' => 15]);
            $falseContext2 = new Context(['age' => 70]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse LIKE operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("email LIKE '%@example.com'");

            $trueContext = new Context(['email' => 'user@example.com']);
            $falseContext = new Context(['email' => 'user@other.com']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse NOT LIKE operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse("email NOT LIKE '%@spam.com'");

            $trueContext = new Context(['email' => 'user@example.com']);
            $falseContext = new Context(['email' => 'user@spam.com']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse IS NULL operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('middle_name IS NULL');

            $trueContext = new Context(['middle_name' => null]);
            $falseContext = new Context(['middle_name' => 'John']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse IS NOT NULL operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('email IS NOT NULL');

            $trueContext = new Context(['email' => 'user@example.com']);
            $falseContext = new Context(['email' => null]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than or equal operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('age <= 18');

            $trueContext1 = new Context(['age' => 18]);
            $trueContext2 = new Context(['age' => 15]);
            $falseContext = new Context(['age' => 20]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse greater than or equal operator', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse('age >= 18');

            $trueContext1 = new Context(['age' => 18]);
            $trueContext2 = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse complex expression with multiple operators', function (): void {
            $parser = new SQLWhereParser();
            $rule = $parser->parse(
                "(age >= 18 AND country = 'US') OR (age >= 21 AND country IN ('CA', 'UK'))",
            );

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext1 = new Context(['age' => 20, 'country' => 'CA']);
            $falseContext2 = new Context(['age' => 15, 'country' => 'US']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception for invalid syntax', function (): void {
            $parser = new SQLWhereParser();

            expect(fn (): Rule => $parser->parse('age > >'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for incomplete expression', function (): void {
            $parser = new SQLWhereParser();

            expect(fn (): Rule => $parser->parse('age >'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for missing operand', function (): void {
            $parser = new SQLWhereParser();

            expect(fn (): Rule => $parser->parse('AND age > 18'))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for unmatched parentheses', function (): void {
            $parser = new SQLWhereParser();

            expect(fn (): Rule => $parser->parse('(age > 18'))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
