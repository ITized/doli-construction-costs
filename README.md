# CONSTRUCTION COSTS FOR [DOLIBARR ERP & CRM](https://www.dolibarr.org)

A comprehensive Dolibarr module for managing construction products, services, Bills of Materials (BOMs) and pricing for the French construction sector.

## Features

### Configurable Pricing Engine
- **Main d'Oeuvre (MO) hourly rate**: Define a global labor rate or override per rule
- **Base multiplier**: Apply multipliers to product and service pricing
- **Margin management**: Configure default margin percentages or override per rule
- **Three pricing modes**: Product-only, Service-only, or Mixed (product + labor)
- **French VAT rates**: Standard (20%), reduced (10% renovation), super-reduced (5.5% energy)
- **Full price breakdown**: Material cost, labor cost, subtotal, margin, HT, VAT, TTC

### Construction-Sector Data
- Pre-built categories: Gros Oeuvre, Second Oeuvre, Electricité, Plomberie, Chauffage, Menuiserie, Peinture, Carrelage, Isolation, Toiture, Terrassement, Maçonnerie, Charpente, Serrurerie, VRD
- Standard measurement units: m², m³, ml, unité, ensemble, kg, tonne, litre, heure, jour, forfait
- Sample CSV data files with 25+ construction products and 20+ construction services

### Multi-Supplier Support
- CSV import with configurable separators and column mapping
- Product matching by EAN/barcode or product reference
- Supplier configuration management (CSV or API integration type)
- Scheduled price synchronization via Dolibarr cron

### Import/Export
- CSV import for construction products and services
- Downloadable CSV template for easy data formatting
- Row-level validation with detailed error reporting
- Configurable CSV separator (semicolon, comma, tab, pipe)

### Dolibarr Integration
- Full module descriptor following Dolibarr module builder template
- Admin setup page with FormSetup configuration factory
- Hooks into product/service cards and BOM cards
- Trigger system for product and BOM events
- Permission system (read/write/delete per object type)
- French and English language files
- Dictionary management for categories and units

## Module Structure

```
htdocs/constructioncosts/
├── admin/                  # Admin configuration pages
│   ├── setup.php           # Module settings (pricing, margins, suppliers)
│   └── about.php           # Module information
├── class/                  # PHP business logic classes
│   ├── pricingrule.class.php              # Pricing rule engine
│   ├── constructioncostscatalog.class.php # CSV import and catalog management
│   └── actions_constructioncosts.class.php # Hook actions
├── core/
│   ├── modules/
│   │   └── modConstructionCosts.class.php # Module descriptor
│   └── triggers/           # Event triggers
├── data/                   # Sample CSV data files
│   ├── sample_products.csv # Construction products with supplier prices
│   └── sample_services.csv # Construction services with MO rates
├── langs/
│   ├── en_US/              # English translations
│   └── fr_FR/              # French translations
├── lib/                    # Library helper functions
├── sql/                    # Database schema and seed data
│   ├── llx_constructioncosts_pricingrule.sql
│   ├── llx_constructioncosts_supplierconfig.sql
│   ├── llx_constructioncosts_dictionaries.sql
│   └── data.sql            # Default categories and units
├── test/phpunit/           # PHPUnit tests
└── constructioncostsindex.php # Module dashboard
```

## Pricing Model

### Product Pricing
```
final_price = (base_price × multiplier) × (1 + margin% / 100)
```

### Service Pricing
```
final_price = (MO_rate × hours × multiplier) × (1 + margin% / 100)
```

### Mixed Pricing (Product + Service)
```
final_price = ((base_price + MO_rate × hours) × multiplier) × (1 + margin% / 100)
```

### Price TTC
```
price_ttc = price_ht × (1 + VAT% / 100)
```

## Installation

### Prerequisites
- Dolibarr ERP & CRM 19.0 or higher
- PHP 7.4 or higher

### From ZIP file
1. Download the latest release as a ZIP file
2. Go to **Home > Setup > Modules > Deploy external module**
3. Upload the ZIP file
4. Enable the module in **Home > Setup > Modules**

### From Git
```bash
cd /path/to/dolibarr/htdocs/custom
git clone https://github.com/ITized/doli-construction-costs.git constructioncosts
```

Then enable the module in Dolibarr: **Home > Setup > Modules**.

## Configuration

After enabling the module, go to **Construction Costs > Setup** to configure:

| Setting | Default | Description |
|---------|---------|-------------|
| MO Hourly Rate | 45.00 EUR | Default labor hourly rate |
| Default Margin | 20% | Margin added to cost price |
| Service Multiplier | 1.00 | Base multiplier for services |
| Default VAT Rate | 20.0% | Standard French VAT rate |
| CSV Separator | ; | Separator for CSV import/export |
| Supplier Sync | Disabled | Automatic price synchronization |

## Development

### Setup
```bash
git clone https://github.com/ITized/doli-construction-costs.git
cd doli-construction-costs
composer install
```

### Run Tests
```bash
composer test        # Run PHPUnit tests
composer lint        # Run PHP syntax checks
composer check       # Run both lint and tests
```

### CI/CD
This project uses GitHub Actions for continuous integration:
- PHP lint across versions 7.4, 8.0, 8.1, 8.2, 8.3
- PHPUnit tests across PHP 7.4, 8.1, 8.3

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) before contributing.

## Translations

Translations can be completed by editing files in `htdocs/constructioncosts/langs/`:
- `en_US/constructioncosts.lang` - English
- `fr_FR/constructioncosts.lang` - French

## License

### Main code
GPLv3 or (at your option) any later version. See [LICENSE](LICENSE) for details.

### Documentation
All texts and readmes are licensed under [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).
