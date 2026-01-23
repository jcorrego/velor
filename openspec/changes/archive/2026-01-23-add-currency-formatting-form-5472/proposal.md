# Change: Add Currency Input Formatting to Form 5472

## Why
Form 5472 contains currency fields (like field "1c. Total assets") that currently render as basic number inputs without proper currency formatting, making them harder for users to read and enter accurately.

## What Changes
- Add currency input formatting for fields with `"type": "currency"` in Form 5472 rendering
- Apply Flux input mask `$money($input)` or similar to provide proper currency formatting
- Ensure formatting works with both display and data entry

## Impact
- Affected specs: finance-management (form rendering)
- Affected code: `/resources/views/components/finance/⚡form-5472.blade.php`
- User experience improvement for tax form data entry