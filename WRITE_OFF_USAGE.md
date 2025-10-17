# How to Record Write-Offs

## Current Situation
Your database has **0 write-off transactions**. The "WRITTEN OFF" column shows $0.00 because there are no transactions with `type = 'write_off'`.

## Transaction Types in Your Database
- `charge`: 14 transactions (amounts owed by customers)
- `credit`: 14 transactions (includes discounts like "family and friends discount")
- `payment`: 10 transactions (actual payments received)

## How to Record a Write-Off

### Method 1: Using the User Model
```php
$customer = User::find($customerId);
$manifest = Manifest::find($manifestId);

// Record a write-off for a specific amount
$customer->recordWriteOff(
    amount: 100.00,
    description: 'Forgiven debt - customer hardship',
    createdBy: auth()->id(),
    referenceType: 'App\\Models\\Manifest',
    referenceId: $manifest->id,
    metadata: ['reason' => 'customer_request', 'approved_by' => 'Manager']
);
```

### Method 2: Direct Transaction Creation
```php
CustomerTransaction::create([
    'user_id' => $customerId,
    'type' => 'write_off',
    'amount' => 100.00,
    'balance_before' => $customer->account_balance,
    'balance_after' => $customer->account_balance, // Balance doesn't change
    'description' => 'Forgiven debt',
    'manifest_id' => $manifestId,  // Link to manifest
    'created_by' => auth()->id(),
]);
```

## Important Notes

### Write-Offs vs Credits
- **Write-Off**: Forgiven debt that reduces what customer owes (doesn't add money to their account)
- **Credit**: Money added to customer's account that they can use for future purchases

### When to Use Write-Offs
- Customer disputes a charge and you agree to forgive it
- Goodwill gesture for service issues
- Promotional discounts after the fact
- Bad debt that won't be collected

### Write-Off Behavior
- Does NOT change the customer's `account_balance`
- DOES reduce the outstanding balance in reports
- DOES count toward collection rate
- Shows in the "WRITTEN OFF" column in reports

## Alternative: Convert Existing Credits to Write-Offs

If you want to treat existing discount credits as write-offs, you can run:

```php
// Find credit transactions that are actually discounts
$discountCredits = CustomerTransaction::where('type', 'credit')
    ->where(function($q) {
        $q->where('description', 'like', '%discount%')
          ->orWhere('description', 'like', '%forgiv%')
          ->orWhere('description', 'like', '%write%');
    })
    ->get();

// Convert them to write_off type
foreach ($discountCredits as $transaction) {
    $transaction->update(['type' => 'write_off']);
}
```

## Testing Write-Offs

### Create a Test Write-Off
```bash
php artisan tinker
```

```php
$customer = User::customers()->first();
$manifest = Manifest::first();

$customer->recordWriteOff(
    100.00,
    'Test write-off for reporting',
    auth()->id(),
    'App\\Models\\Manifest',
    $manifest->id
);
```

Then refresh the Sales & Collections report to see:
- "WRITTEN OFF" column will show $100.00 for that manifest
- "Daily Write-Offs ($)" line will appear on the chart
- Outstanding balance will be reduced by $100.00

## Chart Display
The chart will show:
- **Blue line**: Daily Collections ($) - actual payments received
- **Red line**: Daily Write-Offs ($) - forgiven debt amounts

Both lines will always be visible, even if one is zero.
