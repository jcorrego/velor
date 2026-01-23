# Change: Add entity addresses with modal creation

## Why
Entities need address details for tax reporting and document linkage, and the current inline address creation in assets is inconsistent with a cleaner modal-based UI.

## What Changes
- Allow entities to optionally associate a saved address, with the ability to select an existing address or create a new one.
- Update asset and entity UIs to create new addresses via a modal instead of inline forms.
- Extend address management to cover addresses reusable across assets and entities.

## Impact
- Affected specs: address-management, entity-management, finance-management
- Affected code: entity management UI, asset management UI, address selection/creation components
