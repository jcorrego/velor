## Context
We need an authoritative year-end snapshot of asset and account values to compute total assets per entity for tax reporting. This data must be manually editable by users and scoped to a specific tax year.

## Goals / Non-Goals
- Goals:
  - Allow manual entry and editing of year-end values for accounts and assets.
  - Support per-entity, per-tax-year totals across accounts and assets.
  - Preserve currency context for each value.
- Non-Goals:
  - Automatically compute year-end values from transactions.
  - Backfill historical values without user input.

## Decisions
- Decision: Store year-end values in a single table with optional foreign keys to `accounts` and `assets`.
  - Rationale: A unified structure simplifies totals and validation while keeping the manual-entry workflow consistent across item types.
- Decision: Enforce uniqueness per entity + tax year + item (account or asset).
  - Rationale: Prevents conflicting snapshots for the same year and item.

## Risks / Trade-offs
- Manual data entry can be incomplete. Mitigation: UI prompts and validation for missing items.
- Currency normalization for totals may be required. Mitigation: store currency per value and compute totals in base currency where needed.

## Migration Plan
1. Add a `year_end_values` table and model.
2. Add relationships from `Entity`, `Account`, `Asset`, and `TaxYear`.
3. Implement UI and totals calculation.
4. Add tests and roll out behind existing finance management screens.

## Open Questions
- Should totals always be calculated in the entity’s base currency, or show both native and base currency totals?
