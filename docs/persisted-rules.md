# Persisted Rule Definitions

## Versioning Contract

Persisted rules should be treated as versioned documents, not ad hoc arrays.

- Current documented schema: `schemas/rule-definition.v1.schema.json`
- Current explicit reference syntax: `@path.to.value`

## Recommended Stored Envelope

Store rule payloads with a version field:

```json
{
  "version": "v1",
  "definition": {
    "field": "score",
    "operator": "greaterThanOrEqualTo",
    "value": "@limits.minScore"
  }
}
```

## Migration: Legacy String References

Older payloads may encode references as plain dotted strings:

```json
{
  "field": "score",
  "operator": "greaterThanOrEqualTo",
  "value": "limits.minScore"
}
```

Migrate before compile/evaluate:

```php
use Cline\Ruler\Core\RuleDefinitionMigrator;

$migrated = RuleDefinitionMigrator::migrateLegacyStringReferences($legacy);
```

After migration:

```json
{
  "field": "score",
  "operator": "greaterThanOrEqualTo",
  "value": "@limits.minScore"
}
```

## Compatibility Testing

Repository fixtures and compatibility tests live under:

- `tests/Fixtures/Rules/v1`
- `tests/Unit/Core/RuleSchemaCompatibilityTest.php`

When introducing a new persisted schema version, add new fixture folders and
compatibility tests before changing compile logic.
