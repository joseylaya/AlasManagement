# 01_DATABASE.md
# ALAS Business Manager - Database Specification
Version: 1.0

---

# Purpose

This document defines the database architecture for ALAS Business Manager.

The goal is **not** to build a complex ERP database.

The goal is to create a clean, scalable, maintainable database that solves the daily operational problems of ALAS Clothing while remaining easy to understand and extend.

This document serves as the single source of truth before any Laravel migration is created.

---

# Database Philosophy

The database should prioritize:

- Simplicity
- Data Integrity
- Traceability
- Scalability
- Maintainability

Every table should exist because it solves a real business problem.

Avoid creating tables for future possibilities unless there is a current business need.

---

# Database Engine

Database

MySQL 8+

Storage Engine

InnoDB

Character Set

utf8mb4

Collation

utf8mb4_unicode_ci

Timezone

UTC

Laravel handles timezone conversion.

---

# General Rules

## Rule 1

Every table has a primary key.

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

---

## Rule 2

Every table includes timestamps.

```sql
created_at

updated_at
```

---

## Rule 3

Business tables support soft delete whenever appropriate.

```sql
deleted_at
```

Business history should never be permanently removed.

---

## Rule 4

Every business record stores accountability.

```sql
created_by

updated_by
```

Both fields reference the users table.

---

## Rule 5

Never store money using FLOAT or DOUBLE.

Always use:

```sql
DECIMAL(15,2)
```

Examples

Selling Price

Cost

Expense

Cash

Income

Profit

---

## Rule 6

Never store historical values that depend on another table.

Example

Order Items store:

- product_name
- sku
- selling_price

These are snapshots.

Changing a product tomorrow must never modify historical orders.

---

## Rule 7

Status fields are preferred over boolean fields.

Good

```text
status

active

inactive

archived
```

Avoid

```text
is_active
```

---

## Rule 8

Inventory is never manually edited.

Every inventory change creates a Stock Movement.

Current stock is updated only through controlled inventory operations.

---

## Rule 9

Every financial movement creates a Cash Transaction.

Examples

Sale

Expense

Owner Withdrawal

Refund

Salary

Cash history must always be complete.

---

## Rule 10

Every important user action creates an Activity Log.

Examples

Created Product

Updated Order

Adjusted Inventory

Recorded Expense

Changed Settings

---

# Database Modules

The database is organized by business domain.

---

## Authentication

Responsible for user access.

Tables

```text
users
```

---

## Product Management

Responsible for product catalog.

Tables

```text
products
```

---

## Inventory

Responsible for stock management.

Tables

```text
inventories

stock_movements
```

---

## Sales

Responsible for customer orders.

Tables

```text
orders

order_items
```

---

## Finance

Responsible for cash movement.

Tables

```text
expense_categories

expenses

cash_transactions

owner_drawals
```

---

## System

Responsible for monitoring and configuration.

Tables

```text
activity_logs

notifications

settings
```

---

# Database Structure

```
Users
│
├── Orders
├── Expenses
├── Activity Logs
├── Cash Transactions
└── Stock Movements

Products
│
├── Inventories
│
└── Order Items

Orders
│
└── Order Items

Expenses
│
└── Cash Transactions

Owner Drawals
│
└── Cash Transactions
```

---

# Planned Tables

| Table | Purpose |
|---------|---------|
| users | Employee accounts |
| products | Product catalog |
| inventories | Current inventory |
| stock_movements | Inventory history |
| orders | Customer orders |
| order_items | Products inside orders |
| expense_categories | Expense classification |
| expenses | Business expenses |
| cash_transactions | Complete cash history |
| owner_drawals | Owner withdrawals |
| activity_logs | User audit trail |
| notifications | System alerts |
| settings | Business configuration |

Total

**13 Core Tables**

---

# Standard Columns

Most business tables should include:

```text
id

status

created_by

updated_by

created_at

updated_at

deleted_at
```

These provide:

- Accountability
- History
- Soft delete
- Auditability

---

# Naming Convention

Tables

Plural

Example

```text
products

orders

expenses

cash_transactions
```

Columns

snake_case

Example

```text
selling_price

current_stock

payment_status
```

Foreign Keys

Always end with `_id`

Example

```text
user_id

product_id

order_id

expense_category_id
```

---

# Relationships

One User

↓

Many Orders

Many Expenses

Many Activity Logs

Many Cash Transactions

---

One Product

↓

One Inventory

Many Order Items

Many Stock Movements

---

One Order

↓

Many Order Items

---

One Expense

↓

One Cash Transaction

---

One Owner Drawal

↓

One Cash Transaction

---

# Audit Strategy

Every important business action records:

Who performed it

When it happened

What changed

Activity Logs are immutable.

---

# Inventory Strategy

Inventory is controlled through Stock Movements.

Examples

Purchase

Sale

Adjustment

Damage

Return

Inventory quantity should never be edited directly.

---

# Finance Strategy

The source of truth for money is the `cash_transactions` table.

Every cash movement must be recorded.

Transaction Types

- Sale
- Expense
- Owner Withdrawal
- Salary
- Refund
- Adjustment

The Dashboard calculates:

- Current Cash
- Today's Income
- Today's Expenses
- Monthly Profit
- Available Business Funds

using this table.

---

# Performance Strategy

Create indexes for:

Primary Keys

Foreign Keys

Status

Created Date

Frequently searched columns

Examples

```text
product_name

sku

order_number

expense_date
```

Avoid unnecessary indexes.

---

# Security

Passwords must be hashed.

Soft delete instead of hard delete.

Business history should never be lost.

Only Owners and authorized Managers may access sensitive financial information.

---

# Future Expansion

The schema is intentionally simple.

Future modules may include:

- Suppliers
- Purchase Orders
- Payroll
- Customer Profiles
- Discount Rules
- Gift Cards
- Multi-store
- Barcode Support
- QR Code Inventory
- GCash Integration
- Shipping API Integration

These should be added only when the business requires them.

---

# Development Workflow

For each table:

1. Design the table structure.
2. Review business rules.
3. Define relationships.
4. Create Laravel migration.
5. Create Eloquent Model.
6. Create Factory.
7. Create Seeder.
8. Implement CRUD.
9. Write tests.
10. Deploy.

Do not create all migrations at once.

Develop module by module.

---

# Final Principle

The database exists to support the business—not the other way around.

Every table, every column, and every relationship should have a clear business purpose.

If a table does not solve a real operational problem, it should not exist.

Keep the schema simple, traceable, and maintainable so ALAS Business Manager can grow naturally with the business.