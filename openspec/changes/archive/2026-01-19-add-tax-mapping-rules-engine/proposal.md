# Change: Add Tax Mapping Rules Engine

## Why
Currently, US tax reports (Form 5472 and Schedule E) use hardcoded filters like `LIKE '%rental%'` to determine which transactions belong to which tax forms. This approach is:  
- **Inflexible**: Requires code changes to add new categories
- **Error-prone**: Categories without "rental" in the name are excluded even if they should be on Schedule E
- **Not scalable**: Each tax form needs custom filtering logic

Rule-based category-to-tax-form mapping provides:  
- User control over which categories map to which tax forms
- Support for multiple tax forms per category  
- Jurisdiction-specific mappings
- No code changes needed when adding new categories

## What Changes

### Current Implementation Issues
- `UsTaxReportingService::getScheduleERentalSummary()` filters by `name LIKE '%rental%'` (lines 98, 120)
- This prevents properly named categories like "Repairs & Maintenance" from appearing unless renamed
- Form 5472 only shows `RelatedPartyTransaction` - no category mapping

### Proposed Solution
1. **Use existing `category_tax_mappings` table**:
   - Links `category_id` → `tax_form_code` + `line_item`
   - Supports multiple mappings per category
   - Country-aware

2. **Update tax reporting services**:
   - Replace `LIKE '%rental%'` filters with category mapping lookups
   - Query: `WHERE category_id IN (SELECT category_id FROM tax_form_mappings WHERE filing_type_code = 'SCHEDULE-E')`
   - Support multiple filing types per category

3. **Add mapping management UI**:
   - Users can map categories to tax forms  
   - Preview which transactions will appear on each form
   - Validation and warnings for unmapped categories

### Implementation Phases
**Phase 1: Database & Core Logic** (MVP)
- Update `UsTaxReportingService` to use mappings instead of `LIKE` filters
- Seed default mappings for existing categories (as needed)

**Phase 2: UI & Validation** (Future)
- Add category mapping configuration UI
- Preview functionality
- Unmapped category warnings

## Impact
- **Affected specs**: `tax-form-mapping` (already exists)
- **Affected code**:
- `app/Finance/Services/UsTaxReportingService.php` - Replace LIKE filters
- Seeder updates: Add default mappings
- **Breaking changes**: None - existing categories will be migrated with default mappings
