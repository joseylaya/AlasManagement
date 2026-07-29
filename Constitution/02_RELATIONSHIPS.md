# 02_RELATIONSHIPS.md
# ALAS Business Manager - Entity Relationships
Version: 1.0

---

# Purpose

This document defines how every table in ALAS Business Manager relates to one another.

The goal is to establish a clear and maintainable database relationship structure before implementation.

This document serves as the reference for:

- Database Foreign Keys
- Laravel Eloquent Relationships
- Business Rules
- Reports
- Dashboard Queries

---

# Relationship Philosophy

Relationships should mirror real business operations.

Avoid unnecessary joins.

Avoid over-normalization.

Every relationship should exist because it represents a real business process.

---

# Database Overview

```
Users
│
├── Orders
├── Expenses
├── Cash Transactions
├── Stock Movements
├── Activity Logs
└── Notifications

Products
│
├── Inventory
├── Stock Movements
└── Order Items

Orders
│
├── Order Items
└── Cash Transactions

Expense Categories
│
└── Expenses

Expenses
│
└── Cash Transactions

Owner Drawals
│
└── Cash Transactions
```

---

# Users

Represents employees who use the system.

Relationships

```
User

hasMany Orders

hasMany Expenses

hasMany Stock Movements

hasMany Cash Transactions

hasMany Activity Logs

hasMany Notifications
```

Laravel

```php
hasMany(Order::class)

hasMany(Expense::class)

hasMany(ActivityLog::class)
```

---

# Products

Represents every sellable clothing item.

Relationships

```
Product

hasOne Inventory

hasMany Order Items

hasMany Stock Movements
```

Laravel

```php
hasOne(Inventory::class)

hasMany(OrderItem::class)

hasMany(StockMovement::class)
```

---

# Inventories

Stores current stock.

Relationship

```
Inventory

belongsTo Product
```

Laravel

```php
belongsTo(Product::class)
```

One product has one inventory record.

---

# Stock Movements

Stores inventory history.

Relationship

```
Stock Movement

belongsTo Product

belongsTo User
```

Movement Types

Purchase

Sale

Adjustment

Damage

Return

Manual Correction

---

# Orders

Represents a customer transaction.

Relationship

```
Order

belongsTo User

hasMany Order Items

hasOne Cash Transaction (optional)
```

Laravel

```php
belongsTo(User::class)

hasMany(OrderItem::class)
```

---

# Order Items

Products inside an order.

Relationship

```
Order Item

belongsTo Order

belongsTo Product
```

Snapshot Fields

- Product Name
- SKU
- Unit Price
- Quantity
- Subtotal

Historical data must never change.

---

# Expense Categories

Relationship

```
Expense Category

hasMany Expenses
```

Examples

Rent

Utilities

Packaging

Marketing

Salary

Transportation

Miscellaneous

---

# Expenses

Relationship

```
Expense

belongsTo Expense Category

belongsTo User

hasOne Cash Transaction
```

---

# Cash Transactions

Represents all money movement.

Relationship

```
Cash Transaction

belongsTo User

belongsTo Order (nullable)

belongsTo Expense (nullable)

belongsTo Owner Drawal (nullable)
```

Transaction Types

Sale

Expense

Owner Withdrawal

Salary

Refund

Adjustment

Only one source record should populate the related foreign key.

---

# Owner Drawals

Relationship

```
Owner Drawal

belongsTo User

hasOne Cash Transaction
```

Owner withdrawals are **not** business expenses.

They reduce business cash but should not reduce operating profit.

---

# Activity Logs

Relationship

```
Activity Log

belongsTo User
```

Every important business action creates an activity log.

Examples

Created Product

Updated Inventory

Created Order

Recorded Expense

Updated Settings

---

# Notifications

Relationship

```
Notification

belongsTo User
```

Notifications are personal.

Different users may receive different notifications.

---

# Settings

Settings do not have direct business relationships.

They are loaded globally by the application.

Examples

- Company Name
- Payment Methods
- Expense Categories (future)
- Low Stock Threshold
- Theme
- Business Preferences

---

# Relationship Summary

| Parent | Child | Relationship |
|---------|--------|--------------|
| User | Orders | One to Many |
| User | Expenses | One to Many |
| User | Activity Logs | One to Many |
| User | Cash Transactions | One to Many |
| User | Stock Movements | One to Many |
| User | Notifications | One to Many |
| Product | Inventory | One to One |
| Product | Order Items | One to Many |
| Product | Stock Movements | One to Many |
| Order | Order Items | One to Many |
| Expense Category | Expenses | One to Many |
| Expense | Cash Transaction | One to One |
| Owner Drawal | Cash Transaction | One to One |

---

# Cascading Rules

Avoid database cascade deletes.

Business records should never disappear automatically.

Recommended behavior

```
Delete Product

↓

Prevent deletion if referenced

OR

Archive Product
```

```
Delete User

↓

Prevent deletion

OR

Archive User
```

```
Delete Expense Category

↓

Prevent deletion if in use
```

Always preserve historical data.

---

# Business Rules

## Products

A product cannot exist without inventory.

---

## Orders

An order must contain at least one order item.

---

## Expenses

Every expense creates one cash transaction.

---

## Owner Drawals

Every owner withdrawal creates one cash transaction.

---

## Inventory

Inventory quantity changes only through stock movements.

---

## Activity Logs

Every major CRUD action creates an activity log.

---

## Notifications

Notifications should reference the originating record whenever possible.

---

# Laravel Relationship Standards

Always define relationships in Eloquent Models.

Example

```php
public function inventory()
{
    return $this->hasOne(Inventory::class);
}

public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}
```

Avoid raw joins when Eloquent relationships are appropriate.

---

# Final Principle

Relationships should model how the business actually operates.

Keep them simple.

Keep them explicit.

Protect historical data.

Design for clarity over cleverness.

A developer should be able to understand the database by reading the relationships alone.