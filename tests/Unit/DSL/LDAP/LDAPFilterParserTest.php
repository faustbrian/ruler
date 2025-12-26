<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\LDAP\LDAPFilterParser;

describe('LDAPFilterParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple equality expression', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(country=US)');

            $context = new Context(['country' => 'US']);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse simple equality that fails', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(country=US)');

            $context = new Context(['country' => 'CA']);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse greater than or equal operator', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(age>=18)');

            $trueContext = new Context(['age' => 25]);
            $equalContext = new Context(['age' => 18]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($equalContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than or equal operator', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(price<=100)');

            $trueContext = new Context(['price' => 75]);
            $equalContext = new Context(['price' => 100]);
            $falseContext = new Context(['price' => 150]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($equalContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse AND logical operator', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(&(age>=18)(country=US))');

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse OR logical operator', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(|(age>=21)(country=US))');

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse NOT logical operator', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(!(age<18))');

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse nested logical operators', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(&(|(age>=21)(country=US))(status=active))');

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA', 'status' => 'active']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US', 'status' => 'active']);
            $falseContext1 = new Context(['age' => 18, 'country' => 'CA', 'status' => 'active']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'US', 'status' => 'inactive']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse presence check', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(email=*)');

            $trueContext = new Context(['email' => 'user@example.com']);
            $falseContext = new Context(['email' => null]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse wildcard prefix match', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(name=John*)');

            $trueContext1 = new Context(['name' => 'John']);
            $trueContext2 = new Context(['name' => 'Johnson']);
            $falseContext = new Context(['name' => 'Bob']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse wildcard suffix match', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(email=*@example.com)');

            $trueContext1 = new Context(['email' => 'user@example.com']);
            $trueContext2 = new Context(['email' => 'admin@example.com']);
            $falseContext = new Context(['email' => 'user@test.com']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse wildcard contains match', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(description=*important*)');

            $trueContext1 = new Context(['description' => 'This is important']);
            $trueContext2 = new Context(['description' => 'important note']);
            $falseContext = new Context(['description' => 'regular note']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse approximate match', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(name~=john)');

            $trueContext1 = new Context(['name' => 'john']);
            $trueContext2 = new Context(['name' => 'John']);
            $trueContext3 = new Context(['name' => 'JOHN']);
            $trueContext4 = new Context(['name' => 'johnny']);
            $falseContext = new Context(['name' => 'Bob']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($trueContext3))->toBeTrue()
                ->and($rule->evaluate($trueContext4))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse numeric values', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(age=25)');

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 30]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse boolean true value', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(verified=true)');

            $trueContext = new Context(['verified' => true]);
            $falseContext = new Context(['verified' => false]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse boolean false value', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(active=false)');

            $trueContext = new Context(['active' => false]);
            $falseContext = new Context(['active' => true]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new LDAPFilterParser();
            $executed = false;
            $rule = $parser->parseWithAction('(age>=18)', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new LDAPFilterParser();
            $executed = false;
            $rule = $parser->parseWithAction('(age>=18)', function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parse complex multi-condition filter', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(&(age>=18)(|(country=US)(country=CA))(status=active))');

            $trueContext1 = new Context(['age' => 25, 'country' => 'US', 'status' => 'active']);
            $trueContext2 = new Context(['age' => 20, 'country' => 'CA', 'status' => 'active']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US', 'status' => 'active']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'UK', 'status' => 'active']);
            $falseContext3 = new Context(['age' => 25, 'country' => 'US', 'status' => 'inactive']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse()
                ->and($rule->evaluate($falseContext3))->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        test('parse filter with spaces in value', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(name=John Doe)');

            $trueContext = new Context(['name' => 'John Doe']);
            $falseContext = new Context(['name' => 'John']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse filter with multiple wildcards', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(name=*o*n*)');

            $trueContext1 = new Context(['name' => 'Johnson']);
            $trueContext2 = new Context(['name' => 'John']);
            $falseContext = new Context(['name' => 'Smith']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse float value', function (): void {
            $parser = new LDAPFilterParser();
            $rule = $parser->parse('(price>=99.99)');

            $trueContext = new Context(['price' => 100.50]);
            $falseContext = new Context(['price' => 50.00]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });
    });
});
