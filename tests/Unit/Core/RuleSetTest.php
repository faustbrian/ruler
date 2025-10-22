<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Core\RuleSet;
use Tests\Fixtures\TrueProposition;

describe('RuleSet', function (): void {
    describe('Happy Paths', function (): void {
        test('ruleset creation update and execution', function (): void {
            $context = new Context();
            $true = new TrueProposition();

            $executedActionA = false;
            $ruleA = new Rule($true, function () use (&$executedActionA): void {
                $executedActionA = true;
            });

            $executedActionB = false;
            $ruleB = new Rule($true, function () use (&$executedActionB): void {
                $executedActionB = true;
            });

            $executedActionC = false;
            $ruleC = new Rule($true, function () use (&$executedActionC): void {
                $executedActionC = true;
            });

            $ruleset = new RuleSet([$ruleA]);

            $ruleset->executeRules($context);

            expect($executedActionA)->toBeTrue();
            expect($executedActionB)->toBeFalse();
            expect($executedActionC)->toBeFalse();

            $ruleset->addRule($ruleC);
            $ruleset->executeRules($context);

            expect($executedActionA)->toBeTrue();
            expect($executedActionB)->toBeFalse();
            expect($executedActionC)->toBeTrue();
        });
    });
});
