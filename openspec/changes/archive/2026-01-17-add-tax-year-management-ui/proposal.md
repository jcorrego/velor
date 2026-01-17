# Change: Add Tax Year Management UI

## Why
Tax years are global records but currently cannot be created from the interface, which blocks users from preparing new filing years without manual database updates.

## What Changes
- Add a UI to create and list tax years per jurisdiction.
- Validate uniqueness on (jurisdiction, year) and surface errors in the UI.
- Keep tax year creation restricted to authorized users consistent with existing management screens.

## Impact
- Affected specs: tax-year-filing
- Affected code: tax year models, Livewire management UI, validation requests
