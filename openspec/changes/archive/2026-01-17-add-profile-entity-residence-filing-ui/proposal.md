# Change: Add UI for Managing Profiles, Entities, Residency, and Filings

## Why
The platform has core data models for profiles, entities, residency periods, and filings, but users need a clear UI to create, update, and review this information. A cohesive management experience reduces setup friction and keeps tax year data current.

## What Changes
- Add profile management UI to create and edit jurisdiction-specific profiles, including names, identifiers, and display currencies
- Add residency period management UI to add, edit, and review the residency timeline per user
- Add entity management UI to create and update entities and associate them to jurisdictions
- Add filing management UI to create and update filings per tax year and form type, with status visibility
- Add navigation and routing to access these management screens

## Impact
- Affected specs: `user-management`, `entity-management`, `tax-year-filing`
- Affected code:
  - New Livewire components and Blade views for management screens
  - Updates to routes and navigation
  - Form request validation and authorization updates
  - Pest feature tests for UI flows
