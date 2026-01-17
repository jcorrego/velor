# Specification: FX Management

## Overview
FX (Foreign Exchange) rate management handles currency conversion, rate caching, and per-transaction rate overrides for accurate multi-currency transaction tracking.

## ADDED Requirements

### Requirement: FX Rate Storage and Caching
The system SHALL store and cache daily FX rates to support historical transaction conversions without repeated API calls.

- The system MUST store daily FX rates for all supported currency pairs (EUR/USD, COP/USD, GBP/USD, etc.)
- Each FX rate MUST record: currency_from, currency_to, rate, rate_date, source (ECB, Fixer.io, Manual)
- The system MUST cache FX rates by (currency_from, currency_to, date) to avoid repeated API calls
- FX rates MUST be retrievable for historical dates (transaction date conversions)
- The system MUST prevent duplicate FX rates (unique per currency pair + date)

#### Scenario: Historical rate retrieval for past transaction
- **WHEN** transaction dated 2024-01-15 in EUR needs conversion to USD
- **AND** system checks cache for EUR/USD rate on 2024-01-15
- **THEN** if found in cache, uses cached rate 1.08 (source: ECB)
- **AND** if not cached, fetches from ECB API and stores for future use
- **AND** subsequent EUR transactions on same date reuse cached rate (no API call)

### Requirement: FX Rate Sources and Preferences
The system SHALL support multiple FX rate sources with user-configurable preferences and automatic fallback.

- The system MUST support multiple FX rate sources: ECB (European Central Bank), Fixer.io, OpenExchangeRates, Manual Entry
- Users MUST be able to set preferred sources: ECB for EUR, Fixer.io as fallback
- The system MUST prefer user's preferred source when available
- The system MUST fall back to alternative sources if preferred source unavailable
- The system MUST allow manual rate entry for transactions (override capability)

#### Scenario: Source preference with fallback
- **WHEN** Juan Carlos sets preferred source: ECB for EUR conversions
- **AND** 2024-01-15 transaction in EUR occurs
- **THEN** system attempts ECB API
- **AND** if ECB unavailable, falls back to Fixer.io
- **AND** rate cached as Fixer.io source; user notified
- **AND** next EUR transaction on same date uses cached Fixer.io rate

### Requirement: Transaction-Level FX Rate Override
The system SHALL allow users to override FX rates for individual transactions with audit trail.

- Users MUST be able to override FX rate for individual transactions
- Override MUST record: transaction_id, original_rate, override_rate, reason (e.g., "Bank conversion rate")
- System MUST track rate_source for each transaction: ECB, Fixer.io, Manual Override, or Bank Rate
- Overridden transactions MUST display the override rate in reporting

#### Scenario: User overrides rate due to bank pricing
- **WHEN** transaction: 1,000 EUR on 2024-01-15, auto-converted at ECB rate 1.08 = 1,080 USD
- **AND** user's bank statement shows actual charge: 1,070 USD (bank rate: 1.07)
- **THEN** user overrides transaction rate to 1.07, reason: "Bank actual conversion rate"
- **AND** system updates converted_amount to 1,070 USD
- **AND** report shows override rate with original ECB rate for reference

### Requirement: Historical Rate Retrieval
The system SHALL retrieve FX rates for any historical transaction date with weekend/holiday handling.

- The system MUST retrieve FX rates for transaction dates (potentially in the past)
- If rate not cached, system MUST fetch from preferred source
- System MUST handle weekends/holidays (use most recent available rate)
- System MUST cache retrieved rates for future use

#### Scenario: Weekend transaction rate lookup
- **WHEN** transaction dated 2024-01-13 (Saturday) in EUR
- **AND** system looks for EUR/USD rate on 2024-01-13: not found (weekend, market closed)
- **THEN** falls back to 2024-01-12 (Friday) rate: 1.08
- **AND** uses 1.08 for conversion, caches for 2024-01-13 Saturday
- **AND** subsequent Saturday transactions on same date use cached rate

### Requirement: Multi-Currency Aggregation
The system SHALL compute category totals in user base currency using historical FX rates.

- Category totals MUST be computed in the user's base currency (USD)
- Transactions in different currencies MUST be summed after conversion (not before)
- Tax form line items MUST use base currency amounts
- Jurisdiction-specific totals MUST convert to that jurisdiction's base currency

#### Scenario: Aggregating rental income in multiple currencies
- **WHEN** reporting rental income for tax year 2024
- **AND** Florida property (USD): 12,000 USD rent income
- **AND** Medellín property (COP): 20,000,000 COP at 4,040 COP/USD = 4,950 USD
- **THEN** Schedule E Part I total: 12,000 + 4,950 = 16,950 USD (all converted to USD base currency)

### Requirement: FX Rate Accuracy and Audit Trail
The system SHALL store FX rates with high precision and maintain auditable conversion records.

- FX rates MUST be stored with precision to 6 decimal places (supports all major currency pairs)
- Each rate MUST be auditable: source, date retrieved, date applied, any overrides
- Rate computation MUST show: original_amount × rate = converted_amount

#### Scenario: FX rate audit trail for compliance
- **WHEN** Juan Carlos verifies EUR/USD conversions for tax audit
- **THEN** report shows: [EUR 1000 × 1.08 = USD 1080] (source: ECB, retrieved 2024-01-16, rate date 2024-01-15)
- **AND** override shown separately: [Original ECB rate 1.08, overridden to 1.07, reason: "Bank rate"]
- **AND** all rates stored to 6 decimals: 1.083742 (not rounded to 1.08)

## Data Integrity
- Currency codes MUST be valid ISO 4217 codes
- FX rates MUST be positive decimal numbers (not zero or negative)
- Rate dates MUST be valid dates (not future dates)
- Currency pairs MUST not have currency_from == currency_to (use rate 1.0 instead)
- Override reasons MUST be non-empty when rate is overridden

## Error Handling
- Missing FX rate for required currency pair SHALL raise FxRateNotFoundException
- Invalid currency code SHALL raise InvalidCurrencyException
- Future transaction date (no FX rate available) SHALL raise FxRateUnavailableException
- All FX rate retrieval errors MUST be logged for manual investigation
