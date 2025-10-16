[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

Fluent rule engine with proposition-based evaluation and 50+ operators for building conditional business logic.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/ruler
```

## Documentation

### Core Concepts

- **[Quick Reference](cookbook/quick-reference.md)** - Core concepts and common patterns
- **[DSL Syntax Overview](cookbook/dsl-syntax.md)** - Understanding DSL capabilities

### Domain-Specific Languages

- **[Wirefilter DSL](cookbook/wirefilter-dsl.md)** - Cloudflare-style filtering syntax
- **[SQL WHERE DSL](cookbook/sql-where-dsl.md)** - Familiar SQL WHERE clause syntax
- **[MongoDB Query DSL](cookbook/mongodb-query-dsl.md)** - NoSQL query expressions
- **[GraphQL Filter DSL](cookbook/graphql-filter-dsl.md)** - GraphQL-style filtering
- **[LDAP Filter DSL](cookbook/ldap-filter-dsl.md)** - Directory service queries
- **[JMESPath DSL](cookbook/jmespath-dsl.md)** - JSON path expressions
- **[Natural Language DSL](cookbook/natural-language-dsl.md)** - Human-readable rules

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [bobthecow/Ruler][link-author]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://github.com/faustbrian/ruler/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/ruler.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/ruler.svg

[link-tests]: https://github.com/faustbrian/ruler/actions
[link-packagist]: https://packagist.org/packages/cline/ruler
[link-downloads]: https://packagist.org/packages/cline/ruler
[link-security]: https://github.com/faustbrian/ruler/security
[link-maintainer]: https://github.com/faustbrian
[link-author]: https://github.com/bobthecow/Ruler
[link-contributors]: ../../contributors
