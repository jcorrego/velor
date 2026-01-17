## Context
CSV imports are already supported; PDF statements require extraction and normalization before matching transactions.

## Goals / Non-Goals
- Goals: define a composable PDF parsing framework, enable bank-specific parsers, and produce normalized transaction payloads.
- Non-Goals: implement OCR or vendor integrations in this change.

## Decisions
- Decision: use a parser contract with bank-specific implementations for consistent output.
- Decision: keep PDF parsing opt-in via parser selection to avoid ambiguity.

## Risks / Trade-offs
- Parsing quality varies by bank statement layout; mitigate with parser-specific tests and fixtures.

## Migration Plan
- Add framework alongside existing CSV import service.
- No data migrations required.

## Open Questions
- Which bank PDF formats are first priority?
- Do we need to store the parsed PDF artifacts?
