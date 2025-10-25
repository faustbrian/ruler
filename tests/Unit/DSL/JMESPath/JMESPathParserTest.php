<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\JMESPath\JMESPathParser;

describe('JMESPathParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple comparison expression', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age > `18`');

            $context = new Context(['age' => 25]);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse comparison with field that fails', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age > `18`');

            $context = new Context(['age' => 15]);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse equality operator', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('status == `"active"`');

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical and expression', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age >= `18` && country == `"US"`');

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse logical or expression', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age >= `21` || country == `"US"`');

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse expression with parentheses', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('(age >= `18` && country == `"US"`) || age >= `21`');

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse nested property access', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('user.age >= `18`');

            $trueContext = new Context(['user' => ['age' => 25]]);
            $falseContext = new Context(['user' => ['age' => 15]]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse not equal operator', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('status != `"inactive"`');

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than operator', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age < `18`');

            $trueContext = new Context(['age' => 15]);
            $falseContext = new Context(['age' => 25]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than or equal operator', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age <= `18`');

            $trueContext1 = new Context(['age' => 15]);
            $trueContext2 = new Context(['age' => 18]);
            $falseContext = new Context(['age' => 25]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse greater than or equal operator', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('age >= `18`');

            $trueContext1 = new Context(['age' => 25]);
            $trueContext2 = new Context(['age' => 18]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new JMESPathParser();
            $executed = false;
            $rule = $parser->parseWithAction('age >= `18`', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new JMESPathParser();
            $executed = false;
            $rule = $parser->parseWithAction('age >= `18`', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parse with contains function', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('contains(tags, `"php"`)');

            $trueContext = new Context(['tags' => ['php', 'javascript', 'python']]);
            $falseContext = new Context(['tags' => ['javascript', 'python']]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse with starts_with function', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('starts_with(email, `"admin"`)');

            $trueContext = new Context(['email' => 'admin@example.com']);
            $falseContext = new Context(['email' => 'user@example.com']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse with array filter', function (): void {
            $parser = new JMESPathParser();
            $rule = $parser->parse('users[?age > `18`]');

            $trueContext = new Context([
                'users' => [
                    ['age' => 25, 'name' => 'John'],
                    ['age' => 30, 'name' => 'Jane'],
                ],
            ]);
            $falseContext = new Context([
                'users' => [
                    ['age' => 15, 'name' => 'John'],
                    ['age' => 16, 'name' => 'Jane'],
                ],
            ]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });
    });
});
