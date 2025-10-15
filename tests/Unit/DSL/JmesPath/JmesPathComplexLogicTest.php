<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\DSL\JmesPath\JmesPathRuleBuilder;

test('complex nested AND OR with multiple levels', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        "((age >= `18` && age < `65`) && (country == 'US' || country == 'CA')) && !(status == 'banned')",
    );

    $validContext = new Context(['age' => 30, 'country' => 'US', 'status' => 'active']);
    $bannedContext = new Context(['age' => 30, 'country' => 'US', 'status' => 'banned']);
    $youngContext = new Context(['age' => 16, 'country' => 'US', 'status' => 'active']);
    $wrongCountryContext = new Context(['age' => 30, 'country' => 'UK', 'status' => 'active']);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($bannedContext))->toBeFalse()
        ->and($rule->evaluate($youngContext))->toBeFalse()
        ->and($rule->evaluate($wrongCountryContext))->toBeFalse();
});

test('deeply nested parentheses with mixed operators', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        '(((a == `1` || b == `2`) && (c == `3` || d == `4`)) || ((e == `5` && f == `6`) || (g == `7` && h == `8`)))',
    );

    $context1 = new Context(['a' => 1, 'b' => 0, 'c' => 3, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0]);
    $context2 = new Context(['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 5, 'f' => 6, 'g' => 0, 'h' => 0]);
    $context3 = new Context(['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 7, 'h' => 8]);
    $context4 = new Context(['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0, 'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0]);

    expect($rule->evaluate($context1))->toBeTrue()
        ->and($rule->evaluate($context2))->toBeTrue()
        ->and($rule->evaluate($context3))->toBeTrue()
        ->and($rule->evaluate($context4))->toBeFalse();
});

test('NOT with deeply nested conditions', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        "!((age < `18` || age > `65`) || (status == 'banned' || status == 'suspended'))",
    );

    $validContext = new Context(['age' => 30, 'status' => 'active']);
    $youngContext = new Context(['age' => 16, 'status' => 'active']);
    $oldContext = new Context(['age' => 70, 'status' => 'active']);
    $bannedContext = new Context(['age' => 30, 'status' => 'banned']);
    $suspendedContext = new Context(['age' => 30, 'status' => 'suspended']);

    expect($rule->evaluate($validContext))->toBeTrue()
        ->and($rule->evaluate($youngContext))->toBeFalse()
        ->and($rule->evaluate($oldContext))->toBeFalse()
        ->and($rule->evaluate($bannedContext))->toBeFalse()
        ->and($rule->evaluate($suspendedContext))->toBeFalse();
});

test('complex precedence without parentheses', function (): void {
    $jmes = new JmesPathRuleBuilder();
    // AND has higher precedence than OR in JMESPath
    $rule = $jmes->parse('a == `1` || b == `2` && c == `3`');

    // Should evaluate as: a == 1 OR (b == 2 AND c == 3)
    $aTrue = new Context(['a' => 1, 'b' => 0, 'c' => 0]);
    $bcTrue = new Context(['a' => 0, 'b' => 2, 'c' => 3]);
    $bTrueOnly = new Context(['a' => 0, 'b' => 2, 'c' => 0]);
    $nonTrue = new Context(['a' => 0, 'b' => 0, 'c' => 0]);

    expect($rule->evaluate($aTrue))->toBeTrue()
        ->and($rule->evaluate($bcTrue))->toBeTrue()
        ->and($rule->evaluate($bTrueOnly))->toBeFalse()
        ->and($rule->evaluate($nonTrue))->toBeFalse();
});

test('multiple NOT operators chained', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse('!(!(age >= `18`))');

    // Double negation should equal original condition
    $adultContext = new Context(['age' => 25]);
    $minorContext = new Context(['age' => 15]);

    expect($rule->evaluate($adultContext))->toBeTrue()
        ->and($rule->evaluate($minorContext))->toBeFalse();
});

test('complex real-world eligibility check', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        "((age >= `18` && age <= `65`) && (country == 'US' || country == 'CA')) && ".
        '((income > `50000` && creditScore >= `700`) || (hasGuarantor == `true` && guarantorCreditScore >= `750`)) && '.
        '!(hasBankruptcy == `true` || hasForeclosure == `true`)',
    );

    $qualifiedContext = new Context([
        'age' => 30,
        'country' => 'US',
        'income' => 60_000,
        'creditScore' => 720,
        'hasGuarantor' => false,
        'guarantorCreditScore' => 0,
        'hasBankruptcy' => false,
        'hasForeclosure' => false,
    ]);

    $qualifiedWithGuarantorContext = new Context([
        'age' => 25,
        'country' => 'CA',
        'income' => 40_000,
        'creditScore' => 650,
        'hasGuarantor' => true,
        'guarantorCreditScore' => 780,
        'hasBankruptcy' => false,
        'hasForeclosure' => false,
    ]);

    $bankruptcyContext = new Context([
        'age' => 30,
        'country' => 'US',
        'income' => 80_000,
        'creditScore' => 750,
        'hasGuarantor' => false,
        'guarantorCreditScore' => 0,
        'hasBankruptcy' => true,
        'hasForeclosure' => false,
    ]);

    $lowIncomeContext = new Context([
        'age' => 30,
        'country' => 'US',
        'income' => 30_000,
        'creditScore' => 650,
        'hasGuarantor' => false,
        'guarantorCreditScore' => 0,
        'hasBankruptcy' => false,
        'hasForeclosure' => false,
    ]);

    expect($rule->evaluate($qualifiedContext))->toBeTrue()
        ->and($rule->evaluate($qualifiedWithGuarantorContext))->toBeTrue()
        ->and($rule->evaluate($bankruptcyContext))->toBeFalse()
        ->and($rule->evaluate($lowIncomeContext))->toBeFalse();
});

test('stress test with very deep nesting', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        '((((a == `1` && b == `2`) || (c == `3` && d == `4`)) && '.
        '((e == `5` && f == `6`) || (g == `7` && h == `8`))) || '.
        '(((i == `9` && j == `10`) || (k == `11` && l == `12`)) && '.
        '((m == `13` && n == `14`) || (o == `15` && p == `16`))))',
    );

    $matchFirstPath = new Context([
        'a' => 1, 'b' => 2, 'c' => 0, 'd' => 0,
        'e' => 5, 'f' => 6, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0, 'o' => 0, 'p' => 0,
    ]);

    $matchSecondPath = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 9, 'j' => 10, 'k' => 0, 'l' => 0,
        'm' => 13, 'n' => 14, 'o' => 0, 'p' => 0,
    ]);

    $matchNone = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0, 'o' => 0, 'p' => 0,
    ]);

    expect($rule->evaluate($matchFirstPath))->toBeTrue()
        ->and($rule->evaluate($matchSecondPath))->toBeTrue()
        ->and($rule->evaluate($matchNone))->toBeFalse();
});

test('extreme depth with 12 levels of nesting', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        '((((((((((((a == `1`) && (b == `2`)) || (c == `3`)) && (d == `4`)) || (e == `5`)) && (f == `6`)) || (g == `7`)) && (h == `8`)) || (i == `9`)) && (j == `10`)) || (k == `11`)) && (l == `12`))',
    );

    $allTrue = new Context([
        'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4,
        'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8,
        'i' => 9, 'j' => 10, 'k' => 11, 'l' => 12,
    ]);

    $onlyLast = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 11, 'l' => 12,
    ]);

    $noneTrue = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
    ]);

    expect($rule->evaluate($allTrue))->toBeTrue()
        ->and($rule->evaluate($onlyLast))->toBeTrue()
        ->and($rule->evaluate($noneTrue))->toBeFalse();
});

test('deeply nested access control with complex business rules', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        "((((user.role == 'admin' || user.role == 'moderator') && ".
        '(user.permissions.canEdit == `true` && user.permissions.canDelete == `true`)) || '.
        '((user.isOwner == `true` && resource.isPublic == `false`) && '.
        '!(resource.isLocked == `true` || resource.isArchived == `true`))) && '.
        "((user.accountStatus == 'active' && user.emailVerified == `true`) && ".
        "((user.subscriptionLevel == 'premium' || user.credits > `100`) && ".
        '!(user.isBanned == `true` || user.isSuspended == `true`)))) && '.
        '(((currentTime > resource.publishedAt && currentTime < resource.expiresAt) || '.
        'resource.neverExpires == `true`) && '.
        "(contains(['US', 'EU', 'APAC'], resource.region) && resource.status == 'published'))",
    );

    $adminWithAccessContext = new Context([
        'user' => [
            'role' => 'admin',
            'permissions' => ['canEdit' => true, 'canDelete' => true],
            'isOwner' => false,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'premium',
            'credits' => 50,
            'isBanned' => false,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => true,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 9_999,
            'neverExpires' => false,
            'region' => 'US',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    $ownerWithAccessContext = new Context([
        'user' => [
            'role' => 'user',
            'permissions' => ['canEdit' => false, 'canDelete' => false],
            'isOwner' => true,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'free',
            'credits' => 150,
            'isBanned' => false,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => false,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 9_999,
            'neverExpires' => false,
            'region' => 'EU',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    $bannedUserContext = new Context([
        'user' => [
            'role' => 'admin',
            'permissions' => ['canEdit' => true, 'canDelete' => true],
            'isOwner' => true,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'premium',
            'credits' => 200,
            'isBanned' => true,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => true,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 9_999,
            'neverExpires' => false,
            'region' => 'US',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    $expiredResourceContext = new Context([
        'user' => [
            'role' => 'admin',
            'permissions' => ['canEdit' => true, 'canDelete' => true],
            'isOwner' => false,
            'accountStatus' => 'active',
            'emailVerified' => true,
            'subscriptionLevel' => 'premium',
            'credits' => 50,
            'isBanned' => false,
            'isSuspended' => false,
        ],
        'resource' => [
            'isPublic' => true,
            'isLocked' => false,
            'isArchived' => false,
            'publishedAt' => 1_000,
            'expiresAt' => 3_000,
            'neverExpires' => false,
            'region' => 'US',
            'status' => 'published',
        ],
        'currentTime' => 5_000,
    ]);

    expect($rule->evaluate($adminWithAccessContext))->toBeTrue()
        ->and($rule->evaluate($ownerWithAccessContext))->toBeTrue()
        ->and($rule->evaluate($bannedUserContext))->toBeFalse()
        ->and($rule->evaluate($expiredResourceContext))->toBeFalse();
});

test('maximum depth stress test with 15 nested levels', function (): void {
    $jmes = new JmesPathRuleBuilder();
    $rule = $jmes->parse(
        '(((((((((((((((a == `1`) || (b == `2`)) && (c == `3`)) || (d == `4`)) && (e == `5`)) || (f == `6`)) && (g == `7`)) || (h == `8`)) && (i == `9`)) || (j == `10`)) && (k == `11`)) || (l == `12`)) && (m == `13`)) || (n == `14`)) && (o == `15`))',
    );

    $deepMatch = new Context([
        'a' => 1, 'b' => 0, 'c' => 3, 'd' => 0,
        'e' => 5, 'f' => 0, 'g' => 7, 'h' => 0,
        'i' => 9, 'j' => 0, 'k' => 11, 'l' => 0,
        'm' => 13, 'n' => 0, 'o' => 15,
    ]);

    $noMatch = new Context([
        'a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
        'e' => 0, 'f' => 0, 'g' => 0, 'h' => 0,
        'i' => 0, 'j' => 0, 'k' => 0, 'l' => 0,
        'm' => 0, 'n' => 0, 'o' => 0,
    ]);

    expect($rule->evaluate($deepMatch))->toBeTrue()
        ->and($rule->evaluate($noMatch))->toBeFalse();
});
