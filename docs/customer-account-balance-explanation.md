# Customer Account Balance System - How It Works

## Purpose of Account Balance

The **Account Balance** is a running ledger that tracks the financial relationship between the customer and the shipping company. It works like a bank account or credit account where:

- **Positive Balance** = Customer has prepaid funds available
- **Negative Balance** = Customer owes money for services
- **Zero Balance** = Customer is current with no outstanding charges or prepayments

## How the System Works

### 1. **Package Distribution Process**
When packages are ready for pickup and distributed to customers:

1. **Charge Applied**: The total package cost (freight + customs + storage + delivery) is charged to the customer's account
2. **Payment Collected**: Any cash payment collected from the customer is recorded
3. **Credit Applied**: Any available credit balance is automatically applied to reduce the charge
4. **Balance Updated**: The account balance reflects the net result

**Example**: 
- Package costs $100
- Customer pays $60 cash
- Customer has $50 credit available
- Result: Credit covers $50, cash covers $60, customer gets $10 credit for overpayment

### 2. **Transaction Types**

- **Charges** (decrease account balance):
  - Package distribution costs
  - Additional fees
  - Manual adjustments

- **Payments** (increase account balance):
  - Cash payments from customers
  - Bank transfers
  - Manual credits

- **Credits** (separate credit balance):
  - Overpayments from customers
  - Promotional credits
  - Refunds

### 3. **Balance Application**

**During Package Distribution:**
- System automatically applies available credit balance to reduce charges
- Customer only pays the remaining amount after credit is applied
- Any overpayment becomes new credit balance

**For Future Packages:**
- Positive account balance can cover future package costs
- Credit balance is applied first, then account balance
- Customers with sufficient balance may not need to pay cash

## Real Example - Simba Powell

**Current Status:**
- Account Balance: $1,000.00 (prepaid funds)
- Credit Balance: $0.00 (no overpayment credits)
- Total Available: $1,000.00

**What This Means:**
- Simba has $1,000 in prepaid funds
- Next package distribution will deduct costs from this balance
- If package costs $100, balance becomes $900
- If package costs $1,200, Simba owes $200 cash

**Recent Activity:**
- Received $3,500 payment
- Charged $3,500 for package distribution
- Applied $585 write-off/discount
- Net result: $1,000 remaining balance

## How to Use Account Balance

### For Customers:
1. **Check Balance**: View current account and credit balances
2. **Make Payments**: Add funds to account balance for future packages
3. **Track Usage**: Monitor how package costs affect balance
4. **Apply Credits**: Use available credit to reduce package charges

### For Staff:
1. **Package Distribution**: System automatically applies balances
2. **Manual Adjustments**: Add payments, charges, or credits as needed
3. **Write-offs**: Apply discounts or forgive debts
4. **Balance Monitoring**: Track customer payment patterns

## Benefits

1. **Prepayment**: Customers can prepay for faster package pickup
2. **Credit Management**: Overpayments are tracked and applied automatically
3. **Debt Tracking**: Outstanding balances are clearly visible
4. **Audit Trail**: Complete transaction history for accountability
5. **Flexible Payments**: Customers can pay in advance or after service

## Common Scenarios

### Scenario 1: Prepaid Customer
- Account Balance: $500
- Package Cost: $100
- Result: Balance becomes $400, no cash needed

### Scenario 2: Customer with Debt
- Account Balance: -$50 (owes money)
- Package Cost: $100
- Result: Customer pays $150 cash to cover debt + new package

### Scenario 3: Customer with Credit
- Account Balance: $0
- Credit Balance: $75
- Package Cost: $100
- Result: Credit covers $75, customer pays $25 cash

### Scenario 4: Overpayment
- Package Cost: $100
- Customer Pays: $150
- Result: $50 added to credit balance for future use

## Technical Implementation

The system uses three key fields:
- `account_balance`: Main running balance (can be negative)
- `credit_balance`: Available credits (always positive)
- Transaction history for complete audit trail

All balance changes are logged with:
- Transaction type (charge, payment, credit, etc.)
- Amount and description
- User who made the change
- Reference to related packages/distributions
- Before/after balance amounts