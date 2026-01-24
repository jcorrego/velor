# Refactor Seeders Workflow

## Context
Currently, `DatabaseSeeder.php` contains a mix of application-wide reference data (Currencies, Jurisdictions, Filing Types) and personal development data for the lead developer (Juan Carlos Orrego). This mixing makes it difficult to reset the database to a clean state for testing or for other developers to start without this specific personal data.

## Problem
- **Hardcoded Personal Data**: Specific user details, entities, and accounts are hardcoded in the main seeder.
- **Tightly Coupled**: Cannot seed the reference data without also seeding the personal test data unless lines are commented out.
- **Maintenance**: As the personal test scenario grows, `DatabaseSeeder` becomes cluttered.

## Solution
Refactor the seeding workflow to separate "System Data" from "Personal/Demo Data".

1.  **Extract Personal Data**: Move all data creation related to the specific user (User, UserProfile, Entities, Accounts, Categories) into a new dedicated seeder class, e.g., `PersonalUserSeeder`.
2.  **Clean DatabaseSeeder**: `DatabaseSeeder` should only call reference data seeders (Currencies, Jurisdictions, etc.).
3.  **Optional Execution**: Configurable execution of the personal seeder (e.g., call it explicitly or via a local config flag), or simply keeping it as a separate class that `DatabaseSeeder` calls, which is cleaner to toggle.

## Detailed Design
- Create `database/seeders/PersonalUserSeeder.php`.
- Move the following logic from `DatabaseSeeder::run()` to `PersonalUserSeeder::run()`:
    - User creation (Juan Carlos Orrego)
    - User Profiles (Spain, USA, Colombia)
    - Residency Periods
    - Entities (JCO Services LLC, JCO Spain, JCO Colombia)
    - Accounts (Santander, Mercury, Bancolombia)
    - Categories (Income/Expense categories)
- Update `DatabaseSeeder::run()` to:
    - Call `CurrencySeeder`
    - Call `JurisdictionSeeder`
    - Call `FilingTypeSeeder`
    - Call `PersonalUserSeeder` (We can keep it called by default for now, but encapsulated, or comment it out by default). *Proposal: Call it by default but clearly separated.*

## Benefits
- **Separation of Concerns**: System vs. User data.
- **flexibility**: Developers can choose to seed only reference data.
- **Clarity**: `DatabaseSeeder` becomes a distinct entry point for orchestration.
