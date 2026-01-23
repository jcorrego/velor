## Context
Form 5472 is a US tax form that contains currency fields requiring proper formatting for better user experience. The form system already supports different field types through JSON schema definitions, but currency fields currently render as basic number inputs.

## Goals / Non-Goals
- Goals: Provide intuitive currency formatting for Form 5472 currency fields using Flux UI masks
- Non-Goals: Global currency formatting across all forms (future scope), complex multi-currency support

## Decisions
- Decision: Use Flux UI `mask` attribute with currency formatting for fields with `"type": "currency"`
- Alternatives considered: Manual JavaScript formatting (more complex), custom Livewire component (overkill for this scope)
- Rationale: Leverages existing Flux UI capabilities, minimal code changes

## Technical Approach
- Target specific file: `/resources/views/components/finance/⚡form-5472.blade.php`
- Add new `elseif ($fieldType === 'currency')` condition in field rendering loop
- Use appropriate mask pattern like `$9,999,999.99` or `$money($input)` syntax
- Preserve existing data binding with `wire:model.live.debounce.500ms="formData.{{ $fieldKey }}"`

## Risks / Trade-offs
- Risk: Flux UI mask syntax variations could cause rendering issues
- Trade-off: Specific to Form 5472 vs. generic solution (acceptable for targeted improvement)

## Migration Plan
- Add currency field handling to form-5472.blade.php
- Test with existing currency field "1c" from 5472-2025.json schema
- Validate form submission still preserves numeric values correctly

## Open Questions
- What is the exact Flux UI mask syntax for currency formatting?
- Should formatting include currency symbols or just number formatting?