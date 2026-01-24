# development-workflow Specification

## Purpose
TBD - created by archiving change refactor-seeders-workflow. Update Purpose after archive.
## Requirements
### Requirement: Personal Data Seeding Separation
The system SHALL provide a dedicated seeder for personal development data, separate from system reference data.

#### Scenario: Seed only system data
- **WHEN** the `DatabaseSeeder` is executed without the personal seeder enabled
- **THEN** only currencies, jurisdictions, and filing types SHALL be populated
- **AND** no user modifications SHALL occur

#### Scenario: Seed personal data
- **WHEN** the `PersonalUserSeeder` is executed
- **THEN** the specific development user, profiles, and sample entities SHALL be created
- **AND** they SHALL be linked to the pre-seeded reference data

