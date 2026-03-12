# Contributing to Construction Costs Module

Thank you for considering contributing to the Construction Costs module for Dolibarr! This document provides guidelines and information for contributors.

## Code of Conduct

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) before contributing.

## How to Contribute

### Reporting Bugs

- Use the [GitHub Issues](https://github.com/ITized/doli-construction-costs/issues) tracker
- Describe the bug clearly with steps to reproduce
- Include your Dolibarr version, PHP version, and database type
- Attach screenshots if applicable

### Suggesting Features

- Open a GitHub Issue with the "enhancement" label
- Describe the feature and its use case in the construction sector
- Explain how it fits with the existing module functionality

### Pull Requests

1. Fork the repository
2. Create a feature branch from `main`: `git checkout -b feature/my-feature`
3. Follow the coding standards (see below)
4. Write or update tests for your changes
5. Ensure all tests pass: `composer test`
6. Run the linter: `composer lint`
7. Commit with clear, descriptive messages
8. Push to your fork and open a Pull Request

### Coding Standards

- Follow [Dolibarr coding standards](https://wiki.dolibarr.org/index.php/Language_and_development_rules)
- Use tabs for indentation in PHP files
- Add PHPDoc blocks for all classes and methods
- Use type hints where possible
- Keep methods focused and concise

### Adding Construction Data

If you want to contribute pricing data or product catalogs:

1. Use CSV format compatible with Dolibarr import
2. Place files in the `htdocs/constructioncosts/data/` directory
3. Follow the existing CSV column structure
4. Include source attribution for pricing data
5. Ensure prices are in EUR and tax-exclusive (HT)

### Adding Supplier Integrations

To add a new supplier price source:

1. Create a new class in `htdocs/constructioncosts/class/` implementing the supplier interface
2. Add configuration options in the admin setup
3. Add appropriate language strings
4. Write unit tests for the integration
5. Document any API keys or credentials needed

## Development Setup

### Prerequisites

- PHP 7.4 or higher
- Dolibarr 19.0 or higher
- Composer
- PHPUnit 9.x

### Local Development

```bash
# Clone the repository
git clone https://github.com/ITized/doli-construction-costs.git

# Install dependencies
composer install

# Run tests
composer test

# Run linter
composer lint
```

### Testing

- Unit tests are in `htdocs/constructioncosts/test/phpunit/`
- Tests follow PHPUnit conventions
- Mock Dolibarr database connections where possible
- Integration tests require a running Dolibarr instance

## License

By contributing, you agree that your contributions will be licensed under the GNU General Public License v3.0.
