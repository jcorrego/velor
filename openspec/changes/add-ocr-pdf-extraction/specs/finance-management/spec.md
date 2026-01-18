## MODIFIED Requirements
### Requirement: PDF Statement Parsing Framework
The system SHALL provide a PDF parsing framework to normalize bank statement PDFs into transaction data for import workflows.

#### Scenario: Bank PDF parsed into normalized transactions
- **WHEN** a user uploads a bank statement PDF with a supported parser
- **THEN** the system SHALL extract transaction rows into a normalized transaction array
- **AND** the system SHALL surface parsing errors without importing data

#### Scenario: OCR fallback when PDF has no text rows
- **WHEN** a user uploads a bank statement PDF and text extraction yields no transaction rows
- **AND** the selected parser supports OCR fallback
- **THEN** the system SHALL attempt OCR-based text extraction for the statement pages
- **AND** the system SHALL parse transactions from the OCR output if present
- **AND** the system SHALL surface OCR-related parsing errors without importing data
