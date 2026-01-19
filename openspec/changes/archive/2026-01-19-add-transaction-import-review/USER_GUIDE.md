# User Guide: Reviewing and Approving Import Batches

## Overview
When you import transactions from bank statements (CSV or PDF), they are not immediately added to your accounts. Instead, they are placed in an **Import Batch** for your review. This gives you a chance to verify everything is correct before the transactions affect your tax reports.

## Why Review Before Importing?
- **Catch errors early**: Spot incorrect amounts or dates before they affect reports
- **Verify categories**: Ensure transactions are properly categorized for tax purposes
- **Avoid duplicates**: The system shows which transactions already exist
- **Control your data**: You decide what gets imported and when

## The Import Process

### Step 1: Upload Your Statement
1. Navigate to **Finance → Import Transactions**
2. Select your account (e.g., Banco Santander, Mercury, Bancolombia)
3. Upload your CSV or PDF file
4. Click **Preview Import**

### Step 2: Review the Preview
The preview shows:
- **Total Transactions**: All transactions found in the file
- **New to Import**: Transactions that will be imported (not duplicates)
- **Duplicates (Skipped)**: Transactions already in your account

For each new transaction, you'll see:
- **Description**: Transaction details
- **Date**: When the transaction occurred
- **Counterparty**: Who you paid or received from (if available)
- **Category**: Auto-assigned category (shown in blue)
- **Amount**: Transaction amount and currency

### Step 3: Create Batch for Review
If everything looks good in the preview, click **Create Batch for Review (X Transaction(s))**.

A success message confirms: "Successfully created import batch with X transaction(s)! The batch is now pending review."

### Step 4: Navigate to Review Queue
1. Go to **Management → Import Review** in the sidebar
2. You'll see a list of all pending import batches

## Reviewing a Batch

### Selecting a Batch
Click on any batch in the left panel to view its details. You'll see:
- **Account**: Which account these transactions belong to
- **Transactions**: Number of transactions in the batch
- **Created**: When the batch was created

### Viewing Transactions
Scroll through the full list of transactions that will be imported. Each transaction shows:
- Transaction description
- Date in readable format (e.g., "Dec 31, 2025")
- Counterparty name (if available)
- **Auto-assigned category** in blue (e.g., "Taxes", "Software Subscriptions")
- Amount with color coding:
  - **Green** = income/deposits
  - **Red** = expenses/withdrawals
- Currency (EUR, USD, COP, etc.)

### Category Assignments
Categories are automatically assigned based on description patterns:
- Spanish social security payments → **Taxes**
- Recurring software charges → **Software Subscriptions**
- Bank charges → **Bank Fees**

These can be manually adjusted after import if needed via Transaction Management.

## Approving or Rejecting

### To Approve a Batch
1. Review all transactions carefully
2. Verify categories are correct
3. Check amounts and dates
4. Click **Approve** button
5. Transactions are created and immediately visible in your account
6. The batch status changes to "Applied"

### To Reject a Batch
1. Click in the **Rejection reason** field
2. Explain why you're rejecting (e.g., "Wrong account selected", "Dates are incorrect")
3. Click **Reject** button
4. The batch is marked as "Rejected" and no transactions are created
5. You can re-import with corrections

### After Approval/Rejection
- Approved batches: Transactions appear in **Finance → Transactions**
- Rejected batches: Can be viewed but not edited; reimport with correct settings
- The batch remains in history for audit purposes

## Common Scenarios

### Scenario 1: All Transactions Look Good
✅ Simply click **Approve** and you're done!

### Scenario 2: Wrong Account Selected
❌ Click **Reject** and note "Wrong account - these are personal transactions"
Then re-import selecting the correct account.

### Scenario 3: Some Transactions Need Different Categories
✅ You can still approve! After approval:
1. Go to **Finance → Transactions**
2. Find the transaction
3. Edit and change the category

### Scenario 4: Mix of New and Duplicate Transactions
✅ This is normal! Approve the batch - only NEW transactions will be imported. Duplicates are automatically skipped.

### Scenario 5: Bank Statement Has Errors
❌ Reject the batch with reason "Bank statement has incorrect dates"
Contact your bank for a corrected statement, then re-import.

## Best Practices

### Before Approving
- [ ] Verify the account is correct
- [ ] Spot-check a few transaction amounts against your actual statement
- [ ] Review auto-assigned categories, especially for taxes
- [ ] Check that dates look reasonable
- [ ] Ensure no sensitive transactions are miscategorized

### After Approving
- Approved transactions can still be edited individually
- Categories can be changed anytime
- Transactions can be reconciled once verified
- You can add notes or attach documents to transactions

### When to Reject
- Wrong account selected during import
- File was corrupted or incomplete
- Dates are in the wrong format
- You need to check something before committing
- Test import that shouldn't be in production

## FAQ

**Q: Can I edit transactions before approving?**
A: No, but you can reject and re-import, or approve and edit individual transactions afterward.

**Q: What happens to rejected batches?**
A: They remain visible in the review queue (marked as Rejected) but no transactions are created. You can view them for reference.

**Q: Can I delete a batch after approval?**
A: No, but you can delete the individual transactions via Transaction Management if needed.

**Q: How long do batches stay in the queue?**
A: Indefinitely. Pending batches remain until you approve or reject them.

**Q: Can multiple people review the same batch?**
A: Yes, but only one person can approve/reject. The action is recorded with timestamp and user.

**Q: What if I approve by mistake?**
A: You can manually delete the imported transactions via Transaction Management, though it's tedious. Be careful when approving!

**Q: Do categories affect my tax reports?**
A: Yes! Categories are crucial for accurate tax reporting. Review them carefully, especially for deductible expenses.

**Q: Can I approve partial batches?**
A: No, it's all-or-nothing. To exclude specific transactions, reject the batch and manually import only the ones you want.

**Q: How do I know if a category rule is working?**
A: Check the category shown in blue during preview and review. If it's correct, the rule is working!

## Tips for Efficient Reviews

1. **Regular imports**: Import monthly rather than yearly to keep batches small and manageable

2. **Quick checks**: For regular accounts with consistent transactions, a quick scan is often sufficient

3. **Flag for later**: If unsure, reject with note "Need to verify" and come back when you have more information

4. **Pattern learning**: After a few imports, you'll recognize patterns and can review faster

5. **Category review**: Periodically check that auto-categorization is working as expected

## Troubleshooting

**Problem**: Can't see my batch in the review queue
- Check you're logged in as the correct user
- Verify the batch was created (check for success message)
- Try refreshing the page

**Problem**: Transactions aren't showing in the batch
- The batch might have 0 new transactions (all duplicates)
- Check if `proposed_transactions` field is empty

**Problem**: Categories aren't assigned
- Rules might not exist for your jurisdiction
- Contact admin to create category rules
- You can still approve and categorize manually after

**Problem**: Wrong amounts showing
- This could be a file parsing issue
- Reject the batch and try a different file format
- Contact support if the issue persists

## Related Documentation
- [Admin Guide: Managing Category Rules](./ADMIN_GUIDE.md)
- [OpenSpec Proposal](./proposal.md)
- [Technical Implementation Tasks](./TASKS.md)
