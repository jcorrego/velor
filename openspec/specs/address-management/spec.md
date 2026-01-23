# address-management Specification

## Purpose
TBD - created by archiving change update-asset-addresses. Update Purpose after archive.
## Requirements
### Requirement: Address Management
The system SHALL allow users to create, edit, and manage reusable addresses for use across assets and entities.

- Each address MUST store: country, state/province, city, postal/zip code, address line 1, and optional address line 2.
- Addresses MUST be scoped to the owning user.

#### Scenario: Create an address
- **WHEN** a user submits a new address with required fields
- **THEN** the system SHALL store the address and show it in the address list

#### Scenario: Update an address
- **WHEN** a user edits an existing address they own
- **THEN** the system SHALL persist the updates and reflect them in the list

