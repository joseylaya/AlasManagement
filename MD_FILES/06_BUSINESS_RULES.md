# 06_BUSINESS_RULES.md
# ALAS Business Manager - Business Rules Specification
Version: 1.0

---

# Purpose

This document defines the business rules that govern ALAS Business Manager.

These rules represent how ALAS Clothing operates and how the system should protect the business.

The system should not only record data.

It should prevent mistakes, protect money, maintain accountability, and provide clear business visibility.

---

# Core Business Philosophy

## Rule #1 — Never Make Decisions Based on Bank Balance

The bank balance is not the same as available business funds.

Business decisions should consider:

- Pending expenses
- Supplier obligations
- Employee salary
- Operational needs
- Upcoming commitments

The system should help the owner answer:

> "How much money can I safely use?"

not:

> "How much money is currently in my account?"

---

# Rule #2 — Every Business Action Must Leave Evidence

ALAS Business Manager must always answer:

- Who performed the action?
- What changed?
- When did it happen?
- Why did it happen?

Every important operation must create:

- Database audit fields
- Activity Log entries

No invisible changes are allowed.

---

# Rule #3 — Business Data Is More Valuable Than Transactions

Deleting information destroys business history.

The system should prefer:

- Archive
- Cancel
- Restore

instead of permanent deletion.

Historical records should remain available for analysis.

---

# Product Rules

## Product Creation

A product requires:

Required:

- Product name
- SKU
- Selling price
- Cost price
- Initial stock

Optional:

- Description
- Image
- Category
- Color
- Size

A product cannot be sold unless it is active.

---

## Product Status

Products have lifecycle states:

```
Active

Inactive

Archived
```

Rules:

Active:

- Can appear in sales
- Can receive stock

Inactive:

- Hidden from normal selling
- Historical data remains

Archived:

- Cannot be sold
- Cannot receive stock
- Used only for history

---

## Product Pricing

Products have:

Selling Price

The customer price.

Cost Price

The business acquisition/manufacturing cost.

Profit calculation:

```
Selling Price - Cost Price = Gross Profit
```

Historical transactions must keep their own price snapshot.

Example:

A shirt sells today at $20.

Tomorrow the price becomes $25.

Old orders must still show $20.

---

# Inventory Rules

## Inventory Is Never Manually Changed

The system must never directly update stock quantity.

Incorrect:

```
inventory.stock = 50
```

Correct:

```
Stock Movement

↓

Update Inventory
```

---

# Stock Movement Types

Allowed movements:

```
Initial Stock

Stock Addition

Sale

Return

Damage

Correction

Adjustment
```

---

# Stock Movement Requirements

Every movement requires:

- Product
- Quantity
- Movement Type
- Reason
- User

Example:

```
Product:
ALAS Oversized Tee Black Large

Movement:
Damage

Quantity:
2

Reason:
Defective print

Created by:
John
```

---

# Negative Inventory Rule

Stock cannot become negative.

Example:

Available:

```
5 pieces
```

Customer orders:

```
7 pieces
```

System must prevent checkout.

---

# Low Stock Rule

Every product has a minimum stock threshold.

Example:

```
Minimum Stock: 10
```

When:

```
Current Stock <= Minimum Stock
```

System creates:

- Notification
- Dashboard Alert

---

# Sales Rules

## Order Creation

An order requires:

- At least one product
- Quantity
- Customer information (optional for meetups)
- Payment information

---

# Order Lifecycle

Order Status:

```
Pending

Confirmed

Packed

Shipped

Completed

Cancelled
```

---

# Order Status Rules

## Pending

Initial state.

Inventory may be reserved depending on configuration.

---

## Confirmed

Customer order is accepted.

Stock should be reserved.

---

## Packed

Items are prepared.

Shipping information becomes optional.

---

## Shipped

Requires:

- Shipping details

unless:

```
Delivery Type = Meetup
```

---

## Completed

Final successful transaction.

Creates:

- Final sales record
- Cash Transaction

---

## Cancelled

Rules:

- Cannot reduce business profit
- Reserved stock must return
- Activity Log required

---

# Delivery Rules

Delivery Methods:

```
Shipping

Meetup
```

---

## Shipping

Requires:

- Address
- Contact information
- Shipping status

---

## Meetup

Requires:

- Meetup date
- Location
- Contact information

Shipping details are optional.

---

# Payment Rules

Payment Status:

```
Pending

Partial

Paid

Refunded
```

---

# Payment Validation

An order cannot become Completed unless:

```
Payment Status = Paid
```

unless the owner explicitly allows unpaid orders.

---

# Finance Rules

## Cash Transaction Is The Money Source Of Truth

Every money movement creates a Cash Transaction.

Examples:

Income:

```
Sale
```

Outgoing:

```
Expense

Salary

Owner Withdrawal

Refund
```

---

# Cash Transaction Rules

Cash transactions are:

- Traceable
- Immutable
- Auditable

After creation:

Users cannot silently edit amounts.

Corrections must create adjustment transactions.

---

# Expense Rules

Expenses require:

- Category
- Amount
- Date
- Description
- Recorded By

Examples:

```
Packaging

Transportation

Marketing

Supplier Payment

Salary
```

---

# Expense Categories

Categories should be configurable.

Examples:

```
Operations

Marketing

Supplies

Transportation

Salary

Other
```

---

# Owner Withdrawal Rules

Owner withdrawals are NOT expenses.

Example:

Owner takes $500.

Business records:

```
Cash decreases

Owner Withdrawal increases
```

But:

```
Profit does not decrease
```

Reason:

The owner is taking money from the business, not creating a business cost.

---

# Profit Rules

Basic calculation:

```
Revenue

-

Cost of Goods Sold

-

Operating Expenses

=

Profit
```

Owner withdrawals do not affect profit.

---

# User Rules

## User Accountability

Every employee must have their own account.

No shared accounts.

---

# User Roles

Initial roles:

```
Owner

Manager

Staff
```

---

# Owner

Can:

- View everything
- Manage users
- View finance
- Change settings
- Export reports

---

# Manager

Can:

- Manage operations
- Manage orders
- Manage inventory

Finance permissions are configurable.

---

# Staff

Can:

- Perform assigned tasks

Cannot:

- View sensitive financial data
- Modify system settings

---

# Activity Log Rules

The following actions must be logged:

Products:

- Created
- Updated
- Archived

Inventory:

- Stock Added
- Stock Adjusted
- Stock Deducted

Orders:

- Created
- Updated
- Cancelled
- Completed

Finance:

- Expense Created
- Owner Withdrawal Created

System:

- Login
- Settings Changed
- User Changed

---

# Notification Rules

The system should notify users when action is required.

Examples:

Low Stock:

```
ALAS Oversized Tee Black Large is below minimum stock.
```

Pending Order:

```
Order ORD-000021 requires packing.
```

Financial Alert:

```
Monthly expenses exceeded expected threshold.
```

---

# Data Protection Rules

## Never Hard Delete:

Orders

Order Items

Expenses

Cash Transactions

Stock Movements

Activity Logs

---

# Correction Rules

Incorrect data should be corrected through:

- Adjustment
- Reverse Transaction
- New Record

Never rewrite history.

---

# Dashboard Rules

The dashboard should answer:

## Money

How much money do we have?

## Sales

How much did we sell?

## Inventory

What needs attention?

## Operations

What needs action?

---

# Dashboard Priority

Display:

1. Available Business Funds

2. Pending Orders

3. Low Stock Alerts

4. Today's Sales

5. Today's Expenses

6. Recent Activities

---

# Feature Evaluation Rules

Before adding any feature:

Ask:

## Business Value

Does this solve a real business problem?

## Simplicity

Can employees understand it?

## Accountability

Can we track who used it?

## Financial Impact

Does it affect cash?

If yes:

Create Cash Transaction.

## Inventory Impact

Does it affect stock?

If yes:

Create Stock Movement.

---

# Future Growth Rules

The system should support growth without unnecessary complexity.

Possible future additions:

- Suppliers
- Purchase Orders
- Payroll
- Customer Management
- Multiple Stores
- Barcode System
- Online Store Integration

However:

Do not build future complexity before current business needs.

---

# Final Business Principle

ALAS Business Manager exists to protect three things:

## Money

Know where every peso goes.

## Inventory

Know what exists and where it goes.

## Accountability

Know who performed every action.

The system should help ALAS Clothing make better decisions, avoid financial mistakes, and grow sustainably.

The goal is not to build complicated software.

The goal is to build a reliable business partner.