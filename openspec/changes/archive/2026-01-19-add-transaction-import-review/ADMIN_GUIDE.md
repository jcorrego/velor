# Admin Guide: Managing Category Rules

## Overview
Category rules automatically assign transaction categories based on description patterns. This reduces manual categorization work and ensures consistency across imports.

## Accessing Category Rules Management
1. Navigate to **Management → Description Category Rules** in the sidebar
2. The interface shows jurisdiction selector on the left, rule form and list on the right

## Creating a Category Rule

### Step 1: Select Jurisdiction
Click on a jurisdiction (e.g., Spain, United States, Colombia) to manage rules for that jurisdiction.

### Step 2: Fill in Rule Details
- **Description Pattern**: The text pattern to match at the start of transaction descriptions
  - Example: `Recibo Tgss. Cotizacion` for Spanish social security payments
  - Example: `STARBUCKS` for coffee shop expenses
  - Example: `AWS` for Amazon Web Services charges
  - Matching is case-insensitive and matches from the beginning of the description

- **Category**: Select the transaction category to auto-assign
  - Only categories for the selected jurisdiction are shown
  - Category must already exist (create via Category Management if needed)

- **Notes** (Optional): Add context about the rule
  - Example: "Spanish Social Security (TGSS) contribution payments"
  - Helps other admins understand the rule's purpose

- **Active Toggle**: Enable/disable the rule
  - New rules are active by default
  - Inactive rules are not applied during imports

### Step 3: Save
Click **Save rule** to create the rule. It will immediately be available for new imports.

## Managing Existing Rules

### Viewing Rules
Rules are displayed in a table with:
- **Pattern**: The description pattern (shown in monospace font)
- **Category**: The assigned category name
- **Notes**: Any explanatory notes
- **Status**: Active (green) or Inactive (gray) badge
- **Actions**: Edit and Delete buttons

### Editing a Rule
1. Click **Edit** on the rule you want to modify
2. The form populates with current values
3. Make your changes
4. Click **Update rule** to save
5. Click **Cancel** to discard changes

### Toggling Active Status
Click the Active/Inactive badge to quickly enable or disable a rule without editing.

### Deleting a Rule
1. Click **Delete** on the rule
2. Confirm the deletion in the dialog
3. The rule is permanently removed

## Pattern Matching Behavior

### How Patterns Work
- Patterns match from the **start** of the description (anchor: `^`)
- Matching is **case-insensitive**
- Special characters (dots, spaces) are treated literally
- Pattern `Recibo Tgss.` matches: `Recibo Tgss. Cotizacion 005...`
- Pattern `STARBUCKS` matches: `STARBUCKS STORE #1234` or `starbucks coffee`

### Pattern Examples
| Pattern | Matches | Doesn't Match |
|---------|---------|---------------|
| `AWS` | `AWS Invoice`, `aws services` | `MY AWS BILL` (doesn't start with AWS) |
| `Recibo Tgss.` | `Recibo Tgss. Cotizacion...` | `Recibo de Tgss...` (space difference) |
| `PAYROLL` | `PAYROLL DEPOSIT`, `payroll Jan` | `ADP PAYROLL` (doesn't start with PAYROLL) |

### Best Practices
1. **Be specific enough** to avoid false matches
   - Good: `Recibo Tgss. Cotizacion`
   - Bad: `Recibo` (too broad, matches many receipts)

2. **Test your patterns** by reviewing imports
   - Check the Import Review queue to see if rules are working as expected
   - Adjust patterns if you see miscategorizations

3. **Use consistent patterns** for the same vendor
   - If one transaction is `STARBUCKS #1234`, use `STARBUCKS` not the full text

4. **Document complex rules** in the Notes field
   - Explain why the rule exists
   - Note any edge cases or limitations

## When Rules Are Applied

Rules are applied during:
1. **Import Preview**: Categories are assigned and shown in preview
2. **Batch Creation**: Proposed transactions in batch include category assignments
3. **Batch Review**: Users can see auto-assigned categories before approval
4. **Batch Approval**: Transactions are created with the assigned categories

Rules are **not** retroactively applied to existing transactions.

## Jurisdiction-Specific Rules

Rules are scoped by jurisdiction because:
- Different jurisdictions have different category needs
- Same vendor might be categorized differently by country
- Tax reporting requirements vary by jurisdiction

Each entity uses rules from its jurisdiction:
- Spain entity → Spain rules
- USA entity → USA rules
- Colombia entity → Colombia rules

## Troubleshooting

### Rule Not Matching
1. Check the pattern matches the exact start of the description
2. Verify the rule is Active (green badge)
3. Ensure the rule is in the correct jurisdiction
4. Check for typos or extra spaces in the pattern

### Wrong Category Assigned
1. Review rules for the jurisdiction - there might be a conflicting rule
2. Check rule order - first matching rule wins
3. Edit or delete the incorrect rule
4. Create a more specific rule if needed

### Categories Not Showing
1. Verify categories exist for that jurisdiction
2. Create categories first via Category Management
3. Refresh the page if recently added

## Security & Permissions

Currently, all authenticated users can manage category rules. Consider adding role-based permissions if you need to restrict access to specific admin users.

## Related Documentation
- [User Guide: Reviewing Import Batches](./USER_GUIDE.md)
- [OpenSpec Proposal](./proposal.md)
- [Technical Implementation Tasks](./TASKS.md)
