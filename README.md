# Velor

A multi-jurisdiction tax assistance platform that organizes financial data across Spain, USA, and Colombia. Velor centralizes transactions, assets, and accounts in multiple currencies and maps them to tax-relevant categories for reporting.

## Features

- **Multi-Jurisdiction Support** - Manage tax obligations across Spain, USA, and Colombia with jurisdiction-specific profiles and reporting
- **Multi-Currency Handling** - Track transactions in original currencies with automatic FX conversion using ECB rates
- **Bank Import** - Parse CSV and PDF statements from Santander, Mercury, and Bancolombia with duplicate detection
- **Tax Form Generation** - Generate data for US forms (5472, 1040-NR, 1120, Schedule E), Spain (IRPF, Modelo 720), and Colombia tax returns
- **Entity Management** - Track Individuals and LLCs with their associated accounts, assets, and year-end values
- **Document Storage** - Attach supporting documents to transactions and entities with text extraction

## Tech Stack

| Component | Version |
|-----------|---------|
| PHP | 8.2+ |
| Laravel | 12 |
| Livewire | 4 |
| Volt | 1 |
| Flux UI | 2 (Free) |
| Tailwind CSS | 4 |
| MySQL | 8 |
| Pest | 4 |

## Requirements

- Docker Desktop (for Laravel Sail)
- Node.js 18+

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd velor
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Install dependencies and start containers:
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php84-composer:latest \
       composer install --ignore-platform-reqs
   ```

4. Start the containers:
   ```bash
   ./vendor/bin/sail up -d
   ```

5. Generate application key and run migrations:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate
   ```

6. Install frontend dependencies and build assets:
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

## Development

Start the development environment with hot reloading:

```bash
./vendor/bin/sail composer run dev
```

This runs the Laravel server, queue worker, log viewer, and Vite in parallel.

### Common Commands

```bash
# Start containers
./vendor/bin/sail up -d

# Stop containers
./vendor/bin/sail down

# Run artisan commands
./vendor/bin/sail artisan <command>

# Run all tests with linting
./vendor/bin/sail composer test

# Run tests only
./vendor/bin/sail artisan test --compact

# Run specific test file
./vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php

# Format code
./vendor/bin/sail pint --dirty
```

## Architecture

### Domain Hierarchy

```
User
├── UserProfiles (jurisdiction-specific)
├── Entities (Individual, LLC)
│   ├── Accounts → Transactions, YearEndValues
│   ├── Assets → AssetValuations, YearEndValues
│   └── YearEndValues
└── ResidencyPeriods, Filings
```

### Service Layer

- `app/Services/` - General utilities (import, categorization, FX, documents)
- `app/Finance/Services/` - Domain-specific tax reporting by jurisdiction

### Route Organization

| Route File | Purpose |
|------------|---------|
| `routes/web.php` | Views, auth, dashboard, tax form pages |
| `routes/management.php` | Livewire CRUD for profiles, entities, addresses |
| `routes/finance.php` | RESTful API with Sanctum for financial data |

## API

The finance API is available at `/api/` and requires Sanctum authentication. Endpoints include:

- `GET|POST /api/accounts` - Account management
- `GET|POST /api/assets` - Asset management
- `GET|POST /api/transactions` - Transaction management
- `POST /api/import/preview/{account}` - Preview transaction import
- `POST /api/import/confirm/{account}` - Confirm and apply import

## Testing

Tests are written using Pest. Run the test suite with:

```bash
./vendor/bin/sail artisan test --compact
```

## License

This project is proprietary software.
