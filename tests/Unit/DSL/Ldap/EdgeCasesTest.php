<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Ldap\LdapFilterRuleBuilder;

test('parse multiple OR conditions', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(|(status=active)(status=pending)(status=approved))');

    expect($rule->evaluate(
        new Context(['status' => 'active']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'pending']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'approved']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'rejected']),
        ))->toBeFalse();
});

test('parse multiple AND conditions', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(&(age>=18)(age<=65)(active=true)(verified=true))');

    $validContext = new Context(['age' => 30, 'active' => true, 'verified' => true]);
    $invalidContext = new Context(['age' => 30, 'active' => true, 'verified' => false]);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($invalidContext))->toBeFalse();
});

test('parse deeply nested logic', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(&(|(&(a=1)(b=2))(&(c=3)(d=4)))(e=5))');

    $context1 = new Context(['a' => 1, 'b' => 2, 'e' => 5]);
    $context2 = new Context(['c' => 3, 'd' => 4, 'e' => 5]);
    $context3 = new Context(['a' => 1, 'b' => 2, 'e' => 6]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeFalse();
});

test('parse complex wildcard patterns', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(code=A*B*C)');

    expect($rule->evaluate(
        new Context(['code' => 'ABC']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'AxBxC']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'A123B456C']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['code' => 'ABC123']),
        ))->toBeFalse()
        ->and($rule->evaluate(
            new Context(['code' => 'BCA']),
        ))->toBeFalse();
});

test('parse wildcard with special regex characters', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(path=/home/*/files)');

    expect($rule->evaluate(
        new Context(['path' => '/home/user/files']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['path' => '/home/admin/files']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['path' => '/var/user/files']),
        ))->toBeFalse();
});

test('parse dot notation field paths', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(user.age>=18)');

    $trueContext = new Context(['user' => ['age' => 25]]);
    $falseContext = new Context(['user' => ['age' => 15]]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse deeply nested field paths', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(http.request.uri.path=/api/*)');

    $trueContext = new Context(['http' => ['request' => ['uri' => ['path' => '/api/users']]]]);
    $falseContext = new Context(['http' => ['request' => ['uri' => ['path' => '/home']]]]);

    expect($rule->evaluate($trueContext))->toBeTrue()
        ->and($rule->evaluate($falseContext))->toBeFalse();
});

test('parse null comparison', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(value=null)');

    expect($rule->evaluate(
        new Context(['value' => null]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['value' => 'something']),
        ))->toBeFalse();
});

test('parse empty string wildcard', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(prefix=*)');

    // This is a presence check, not wildcard match
    expect($rule->evaluate(
        new Context(['prefix' => 'anything']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['prefix' => '']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['prefix' => null]),
        ))->toBeFalse();
});

test('parse single asterisk at start', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(name=*Smith)');

    expect($rule->evaluate(
        new Context(['name' => 'John Smith']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Smith']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'John Jones']),
        ))->toBeFalse();
});

test('parse single asterisk at end', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(name=Mr*)');

    expect($rule->evaluate(
        new Context(['name' => 'Mr Smith']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Mr']),
        ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['name' => 'Mrs Smith']),
        ))->toBeTrue() // "Mrs" starts with "Mr"
        ->and($rule->evaluate(
            new Context(['name' => 'Dr Smith']),
        ))->toBeFalse();
});

test('parse double negation', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(!(!(status=active)))');

    expect($rule->evaluate(
        new Context(['status' => 'active']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['status' => 'inactive']),
        ))->toBeFalse();
});

test('parse less than operator', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(age<18)');

    expect($rule->evaluate(
        new Context(['age' => 15]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['age' => 18]),
        ))->toBeFalse()
        ->and($rule->evaluate(
            new Context(['age' => 20]),
        ))->toBeFalse();
});

test('parse greater than operator', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(age>65)');

    expect($rule->evaluate(
        new Context(['age' => 70]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['age' => 65]),
        ))->toBeFalse()
        ->and($rule->evaluate(
            new Context(['age' => 30]),
        ))->toBeFalse();
});

test('parse whitespace handling', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule1 = $ldap->parse('( age >= 18 )');
    $rule2 = $ldap->parse('(&  (age>=18)  (country=US)  )');

    expect($rule1->evaluate(
        new Context(['age' => 20]),
    ))->toBeTrue()
        ->and($rule2->evaluate(
            new Context(['age' => 20, 'country' => 'US']),
        ))->toBeTrue();
});

test('parse field names with dots', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(user.profile.name=John*)');

    $context = new Context(['user' => ['profile' => ['name' => 'John Doe']]]);

    expect($rule->evaluate($context))->toBeTrue();
});

test('parse field names with underscores and hyphens', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(&(first_name=John)(last-name=Doe))');

    $context = new Context(['first_name' => 'John', 'last-name' => 'Doe']);

    expect($rule->evaluate($context))->toBeTrue();
});

test('parse complex real-world filter', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse(
        '(&(age>=18)(age<=65)(|(country=US)(country=CA))(emailVerified=true)(!(|(status=banned)(status=suspended))))',
    );

    $validUser = new Context([
        'age' => 30,
        'country' => 'US',
        'emailVerified' => true,
        'status' => 'active',
    ]);

    $bannedUser = new Context([
        'age' => 30,
        'country' => 'US',
        'emailVerified' => true,
        'status' => 'banned',
    ]);

    $youngUser = new Context([
        'age' => 16,
        'country' => 'US',
        'emailVerified' => true,
        'status' => 'active',
    ]);

    expect($rule->evaluate($validUser))->toBeTrue()
        ->and($rule->evaluate($bannedUser))->toBeFalse()
        ->and($rule->evaluate($youngUser))->toBeFalse();
});

test('parse product search filter', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(&(category=electronics)(price>=10)(price<=500)(inStock=true)(|(featured=true)(rating>=4.0)))');

    $validProduct = new Context([
        'category' => 'electronics',
        'price' => 299.99,
        'inStock' => true,
        'featured' => true,
        'rating' => 3.5,
    ]);

    $outOfStock = new Context([
        'category' => 'electronics',
        'price' => 299.99,
        'inStock' => false,
        'featured' => true,
        'rating' => 4.5,
    ]);

    expect($rule->evaluate($validProduct))->toBeTrue()
        ->and($rule->evaluate($outOfStock))->toBeFalse();
});

test('parse content moderation filter', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(|(reportCount>=5)(&(userReputation<10)(linkCount>3))(content=*spam*))');

    $highReports = new Context(['reportCount' => 6, 'userReputation' => 50, 'linkCount' => 1, 'content' => 'Normal content']);
    $lowRepWithLinks = new Context(['reportCount' => 2, 'userReputation' => 5, 'linkCount' => 5, 'content' => 'Check out these links']);
    $spamContent = new Context(['reportCount' => 1, 'userReputation' => 50, 'linkCount' => 0, 'content' => 'This is spam content']);
    $normalContent = new Context(['reportCount' => 1, 'userReputation' => 50, 'linkCount' => 1, 'content' => 'Normal content']);

    expect($rule->evaluate($highReports))->toBeTrue()
        ->and($rule->evaluate($lowRepWithLinks))->toBeTrue()
        ->and($rule->evaluate($spamContent))->toBeTrue()
        ->and($rule->evaluate($normalContent))->toBeFalse();
});

test('parse zero value comparisons', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(count=0)');

    expect($rule->evaluate(
        new Context(['count' => 0]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['count' => 1]),
        ))->toBeFalse();
});

test('parse negative number comparisons', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(temperature<0)');

    expect($rule->evaluate(
        new Context(['temperature' => -5]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['temperature' => 5]),
        ))->toBeFalse();
});

test('parse false boolean value', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(active=false)');

    expect($rule->evaluate(
        new Context(['active' => false]),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['active' => true]),
        ))->toBeFalse();
});

test('parse empty filter throws exception', function (): void {
    $ldap = new LdapFilterRuleBuilder();

    expect(fn (): Rule => $ldap->parse('()'))->toThrow(RuntimeException::class);
});

test('parse filter with parentheses in value', function (): void {
    $ldap = new LdapFilterRuleBuilder();
    $rule = $ldap->parse('(description=text(with)parens)');

    expect($rule->evaluate(
        new Context(['description' => 'text(with)parens']),
    ))->toBeTrue()
        ->and($rule->evaluate(
            new Context(['description' => 'text']),
        ))->toBeFalse();
});

test('parse invalid filter item throws exception', function (): void {
    $ldap = new LdapFilterRuleBuilder();

    expect(fn (): Rule => $ldap->parse('(invalid)'))->toThrow(InvalidArgumentException::class);
});
