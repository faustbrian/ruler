<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\LDAP\LDAPFilterRuleBuilder;

test('parse simple comparison expression', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(age>18)');

    $context = new Context(['age' => 25]);

    expect($rule)->toBeInstanceOf(Rule::class)
        ->and($rule->evaluate($context))->toBeTrue();
});

test('parse comparison with field that fails', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(age>18)');

    $context = new Context(['age' => 15]);

    expect($rule->evaluate($context))->toBeFalse();
});

test('parse equality operator', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(status=active)');

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse logical and expression', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(&(age>=18)(country=US))');

    $trueContext = new Context(['age' => 25, 'country' => 'US']);
    $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
    $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext1))->toBeFalse()
        ->and($rule->evaluate($falseContext2))->toBeFalse();
});

test('parse logical or expression', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(|(age>=21)(country=US))');

    $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
    $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse expression with nested logic', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(|(&(age>=18)(country=US))(age>=21))');

    $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
    $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
    $falseContext = new Context(['age' => 18, 'country' => 'CA']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse not expression', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(!(age<18))');

    $trueContext = new Context(['age' => 25]);
    $falseContext = new Context(['age' => 15]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parseWithAction executes callback when true', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $executed = false;
    $rule = $ldap->parseWithAction('(age>=18)', function () use (&$executed): void {
        $executed = true;
    });

    $context = new Context(['age' => 25]);
    $rule->execute($context);

    expect($executed)->toBeTrue();
});

test('parseWithAction does not execute callback when false', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $executed = false;
    $rule = $ldap->parseWithAction('(age>=18)', function () use (&$executed): void {
        $executed = true;
    });

    $context = new Context(['age' => 15]);
    $rule->execute($context);

    expect($executed)->toBeFalse();
});

test('parse wildcard prefix pattern', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(name=John*)');

    $trueContext = new Context(['name' => 'John Doe']);
    $falseContext = new Context(['name' => 'Jane Doe']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse wildcard suffix pattern', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(email=*@example.com)');

    $trueContext = new Context(['email' => 'john@example.com']);
    $falseContext = new Context(['email' => 'john@test.com']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse wildcard contains pattern', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(description=*important*)');

    $trueContext = new Context(['description' => 'This is important stuff']);
    $falseContext = new Context(['description' => 'Nothing to see here']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse less than or equal operator', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(age<=65)');

    $trueContext = new Context(['age' => 30]);
    $falseContext = new Context(['age' => 70]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse inequality operator', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(status!=inactive)');

    $trueContext = new Context(['status' => 'active']);
    $falseContext = new Context(['status' => 'inactive']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse presence check', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(email=*)');

    $trueContext = new Context(['email' => 'test@example.com']);
    $falseContext = new Context(['email' => null]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse negated presence check', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(!(deletedAt=*))');

    $trueContext = new Context(['deletedAt' => null]);
    $falseContext = new Context(['deletedAt' => '2023-01-01']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse approximate match', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(name~=john)');

    $trueContext1 = new Context(['name' => 'John']);
    $trueContext2 = new Context(['name' => 'JOHN']);
    $trueContext3 = new Context(['name' => 'Johnny']);
    $falseContext = new Context(['name' => 'Jane']);

    expect($rule->evaluate($trueContext1))->toBeTrue()
        ->and($rule->evaluate($trueContext2))->toBeTrue()
        ->and($rule->evaluate($trueContext3))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse boolean values', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(verified=true)');

    $trueContext = new Context(['verified' => true]);
    $falseContext = new Context(['verified' => false]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse numeric values', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(age=18)');

    $trueContext = new Context(['age' => 18]);
    $falseContext = new Context(['age' => 20]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse float values', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(price>=19.99)');

    $trueContext = new Context(['price' => 29.99]);
    $falseContext = new Context(['price' => 9.99]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse complex nested conditions', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(&(|(age>=18)(vip=true))(country=US)(!(status=banned)))');

    $validContext = new Context(['age' => 20, 'vip' => false, 'country' => 'US', 'status' => 'active']);
    $invalidContext1 = new Context(['age' => 16, 'vip' => false, 'country' => 'US', 'status' => 'active']);
    $invalidContext2 = new Context(['age' => 20, 'vip' => false, 'country' => 'CA', 'status' => 'active']);
    $invalidContext3 = new Context(['age' => 20, 'vip' => false, 'country' => 'US', 'status' => 'banned']);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($invalidContext1))->toBeFalse()
        ->and($rule->evaluate($invalidContext2))->toBeFalse()
        ->and($rule->evaluate($invalidContext3))->toBeFalse();
});

test('parse very compact expression', function (): void {
    $ldap = new LDAPFilterRuleBuilder();
    $rule = $ldap->parse('(&(age>=18)(country=US))');

    $trueContext = new Context(['age' => 20, 'country' => 'US']);
    $falseContext = new Context(['age' => 16, 'country' => 'US']);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('validate returns true for valid filter', function (): void {
    $ldap = new LDAPFilterRuleBuilder();

    expect($ldap->validate('(age>=18)'))->toBeTrue()
        ->and($ldap->validate('(&(age>=18)(country=US))'))->toBeTrue();
});

test('validate returns false for invalid filter', function (): void {
    $ldap = new LDAPFilterRuleBuilder();

    expect($ldap->validate('age>=18'))->toBeFalse() // missing parens
        ->and($ldap->validate('(&(age>=18)'))->toBeFalse() // unbalanced parens
        ->and($ldap->validate('(&)'))->toBeFalse(); // no conditions in AND
});
