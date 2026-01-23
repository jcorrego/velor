# Change: Add address management and link assets to addresses

## Why
Asset locations should be tracked through reusable addresses, and asset jurisdiction should be derived from the owning entity to avoid duplicate sources of truth.

## What Changes
- **BREAKING** Remove `jurisdiction` from assets; assets inherit jurisdiction from the entity.
- Add an Address model with management UI (country, state, postal/zip, city, address line 1/2).
- Allow assets to select an existing address or create a new one inline from the asset UI.

## Impact
- Affected specs: finance-management, address-management (new)
- Affected code: asset model/schema/API/UI, new address model/schema/UI, asset forms and validation
