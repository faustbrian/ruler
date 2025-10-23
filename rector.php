<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Carbon\Rector\New_\DateTimeInstanceToCarbonRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withSkip([
        RemoveUnreachableStatementRector::class => [__DIR__.'/tests'],
        LocallyCalledStaticMethodToNonStaticRector::class => [
            __DIR__.'/src/Variables/VariableProperty.php',
            __DIR__.'/src/Operators/Date/After.php',
            __DIR__.'/src/Operators/Date/Before.php',
            __DIR__.'/src/Variables/Value.php',
            __DIR__.'/src/Core/Context.php',
            __DIR__.'/src/Operators/Date/IsBetweenDates.php',
            __DIR__.'/src/Builder/VariableProperty.php',
            __DIR__.'/src/DSL/Natural/NaturalLanguageParser.php',
            __DIR__.'/src/DSL/GraphQL/GraphQLParser.php',
            __DIR__.'/src/DSL/JMESPath/JMESPathAdapter.php',
            __DIR__.'/src/DSL/LDAP/LDAPLexer.php',
            __DIR__.'/src/DSL/LDAP/LDAPCompiler.php',
            __DIR__.'/src/DSL/MongoDB/MongoQueryCompiler.php',
            __DIR__.'/src/DSL/Wirefilter/ExpressionParser.php',
            __DIR__.'/src/DSL/Wirefilter/RuleCompiler.php',
            __DIR__.'/src/DSL/GraphQL/GraphQLFilterValidator.php',
            __DIR__.'/src/DSL/JMESPath/JMESPathSerializer.php',
            __DIR__.'/src/DSL/Wirefilter/WirefilterSerializer.php',
            __DIR__.'/src/DSL/Wirefilter/WirefilterValidator.php',
            __DIR__.'/src/DSL/LDAP/LDAPFilterSerializer.php',
            __DIR__.'/src/DSL/MongoDB/MongoQuerySerializer.php',
            __DIR__.'/src/DSL/Natural/ASTParser.php',
            __DIR__.'/src/DSL/Natural/NaturalLanguageSerializer.php',
            __DIR__.'/src/DSL/SQL/SQLWhereSerializer.php',
            __DIR__.'/src/DSL/GraphQL/GraphQLFilterSerializer.php',
        ],
        ParamTypeByParentCallTypeRector::class => [
            __DIR__.'/src/Variables/VariableProperty.php',
            __DIR__.'/src/Operators/Date/After.php',
            __DIR__.'/src/Operators/Date/Before.php',
        ],
        DateTimeInstanceToCarbonRector::class => [
            __DIR__.'/tests/Unit/Operators/Date/BeforeTest.php',
        ],
    ])
    ->withPhpSets(php84: true)
    ->withParallel(maxNumberOfProcess: 8)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(
        phpunit: true,
        laravel: true,
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: false,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
        // LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
    ])
    ->withRootFiles();
