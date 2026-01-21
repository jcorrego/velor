## Context
Form 5472 requires user guidance and supplemental data fields not derived from transactions. These fields can change across tax years, so the form definition must be versioned by year.

## Goals / Non-Goals
- Goals:
  - Provide section-by-section guidance for Form 5472.
  - Capture and store non-transaction data per filing.
  - Track due dates per filing year and form type.
- Non-Goals:
  - Automatic tax advice or filing submission.
  - Full support for all IRS forms in this change.

## Decisions
- Decision: Store form definitions as versioned schemas keyed by tax form and tax year, with sections/fields/help content.
- Decision: Store user-entered form responses per filing record (or related table) using JSON with schema linkage.
- Decision: Capture due date as part of the filing record when a filing is created for a specific year and form type.

## Risks / Trade-offs
- Schema versioning adds complexity to validation and migrations; mitigate with clear version identifiers and migration notes.
- JSON storage is flexible but requires careful validation to prevent inconsistent data.

## Migration Plan
- Add schema definition records for Form 5472 per supported tax year.
- Add storage for per-filing Form 5472 data and due dates.
- Backfill due dates for existing Form 5472 filings where possible.

## Open Questions
- None.