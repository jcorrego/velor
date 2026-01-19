# Transaction Import Review - Quick Start Guide

## For Users

### Importing Transactions

1. Go to **Finance** section
2. Scroll to **Import Transactions**
3. Select account and file type (CSV or PDF)
4. Upload your file
5. Review the preview showing:
   - Total transactions
   - New transactions to import
   - Duplicates (skipped)
6. Click **Import X Transaction(s)** to create batch
7. Success message shows: "Import batch created. Please review and approve in the Import Review queue."

### Reviewing and Approving Batches

1. Go to **Management → Import Review**
2. See list of pending batches with:
   - Account name
   - Transaction count
   - Creation time
3. Click on batch to review details
4. Right panel shows batch information
5. Choose one of two actions:

**Approve Batch:**
- Click green **Approve** button
- Transactions are immediately created in account
- Categories auto-assigned based on rules
- Batch marked as Applied

**Reject Batch:**
- Enter reason in text area
- Click red **Reject** button
- No transactions created
- Batch marked as Rejected for audit trail

## For Administrators (Future)

### Managing Category Rules

Once admin UI is built, you can:

1. Go to **Management → Description Category Rules**
2. Create rules per jurisdiction:
   - **Pattern**: Text to match at start of description (e.g., "STARBUCKS")
   - **Category**: Category to assign when pattern matches
   - **Active**: Toggle rule on/off without deleting
3. Rules applied automatically during batch approval

### Example Rules

| Pattern | Category | Result |
|---------|----------|--------|
| STARBUCKS | Coffee Expense | "STARBUCKS 123 NYC" → Coffee Expense |
| AWS | Cloud Services | "AWS Invoice #123" → Cloud Services |
| PAYROLL | Wages Expense | "PAYROLL DEPOSIT" → Wages Expense |
| TRANSFER | Transfer | "TRANSFER TO SAVINGS" → Transfer |

## How It Works

### The Import Flow

```
Upload File
    ↓
Parse CSV/PDF
    ↓
Create ImportBatch (Pending)
    ↓
Review Queue Shows Batch
    ↓
User Reviews & Decides
    ├─ Approve →  Create Transactions + Apply Rules → Applied
    └─ Reject  →  No Transactions → Rejected
```

### Category Assignment

When you approve a batch:
1. System loads all active category rules for your jurisdiction
2. For each transaction in batch:
   - Check if description starts with any rule pattern (case-insensitive)
   - If match found → assign category
   - If no match → create without category (can add later)
3. All transactions finalized to your account

## Database Tables

### import_batches
Stores pending import batches with proposed transactions.
- `status`: Pending, Applied, or Rejected
- `proposed_transactions`: JSON array of transaction data
- `rejection_reason`: Why batch was rejected
- `approved_by` / `approved_at`: Who and when approved

### description_category_rules
Stores category mapping rules per jurisdiction.
- `description_pattern`: Text to match at description start
- `category_id`: Category to assign on match
- `is_active`: Whether rule is active

## Files Involved

### Livewire Components
- `ImportReviewQueue` - Review queue interface
- `transaction-import-form` - Upload form (modified)

### Models
- `ImportBatch` - Batch entity
- `DescriptionCategoryRule` - Category rule entity

### Services
- `TransactionImportService` - Handles import and rule application
- `TransactionCategorizationService` - Applies rules during creation

### Routes
- `management.import-review` - Review queue page

## Testing

Run tests with:
```bash
php artisan test tests/Feature/Feature/ImportBatchReviewTest.php --compact
php artisan test tests/Feature/Feature/Finance/TransactionImportTest.php --compact
```

All 329 tests passing ✓

## Troubleshooting

### Import seems to work but transactions not showing

1. Check **Management → Import Review**
2. Is batch showing as "Pending"?
3. If yes, click batch and approve it
4. Transactions should appear immediately

### Categories not assigned automatically

1. Check if category rules exist for your jurisdiction
2. Check rule patterns match your transaction descriptions
3. Patterns are case-insensitive but must match description start
4. Example: Pattern "STARBUCKS" matches "starbucks 123 NYC"

### Can't delete a batch

By design, rejected batches are preserved for audit trail. You can:
- Create new batch if needed
- Mark rule as inactive (for rules)
- Manually delete via database if absolutely necessary (contact admin)

## API Endpoints

### Create Batch
```
POST /api/import/confirm/{account_id}
- file: CSV/PDF file
- parser_type: santander|mercury|bancolombia

Response: { batch_id, transaction_count, duplicates, message }
```

### Preview Transactions
```
POST /api/import/preview/{account_id}
- file: CSV/PDF file
- parser_type: santander|mercury|bancolombia

Response: { matched, unmatched, total, duplicates, new }
```

## Performance Notes

- Batches stored with JSON proposed_transactions for quick loading
- Rules loaded once per batch (minimal database calls)
- Pattern matching is efficient regex
- No performance impact on normal transaction operations
