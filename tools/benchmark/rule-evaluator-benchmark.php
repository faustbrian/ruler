<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../../vendor/autoload.php';

use Cline\Ruler\Core\RuleEvaluator;

/**
 * @return float milliseconds
 */
function elapsedMilliseconds(int $startNs): float
{
    return (hrtime(true) - $startNs) / 1_000_000;
}

$definition = [
    'combinator' => 'and',
    'value' => [
        [
            'field' => 'score',
            'operator' => 'greaterThanOrEqualTo',
            'value' => '@limits.minScore',
        ],
        [
            'field' => 'status',
            'operator' => 'sameAs',
            'value' => 'active',
        ],
        [
            'field' => 'age',
            'operator' => 'greaterThan',
            'value' => 21,
        ],
    ],
];

$samples = 20_000;
$contexts = [];
for ($index = 0; $index < $samples; ++$index) {
    $contexts[] = [
        'score' => $index % 101,
        'limits' => ['minScore' => 60],
        'status' => $index % 3 === 0 ? 'inactive' : 'active',
        'age' => 18 + ($index % 50),
    ];
}

$compileStart = hrtime(true);
$compiled = RuleEvaluator::compileFromArray($definition);
$compileMs = elapsedMilliseconds($compileStart);

if (!$compiled->isSuccess()) {
    $error = $compiled->getError();
    printf("Compile failed: %s\n", $error?->getMessage() ?? 'unknown error');
    exit(1);
}

$evaluator = $compiled->getEvaluator();
$evaluateStart = hrtime(true);
$matches = 0;
foreach ($contexts as $context) {
    if ($evaluator->evaluateFromArray($context)->getResult()) {
        ++$matches;
    }
}
$evaluateMs = elapsedMilliseconds($evaluateStart);

printf(
    "RuleEvaluator benchmark\ncompile_ms=%s\nevaluate_ms=%s\ncontexts=%d\nmatches=%d\nthroughput_ctx_per_s=%s\n",
    number_format($compileMs, 3, '.', ''),
    number_format($evaluateMs, 3, '.', ''),
    $samples,
    $matches,
    number_format($samples / ($evaluateMs / 1000), 2, '.', ''),
);
