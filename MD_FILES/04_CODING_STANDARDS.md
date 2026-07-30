# 04_CODING_STANDARDS.md
# ALAS Business Manager - Coding Standards
Version: 1.0

---

# Purpose

This document defines the coding standards for ALAS Business Manager.

The objective is to ensure that every feature is:

- Consistent
- Readable
- Maintainable
- Testable
- Scalable

These standards apply to all future development.

---

# Core Philosophy

The code should be easy to understand.

Always optimize for readability before cleverness.

A developer should understand a feature without needing lengthy explanations.

---

# Development Principles

## 1. Single Responsibility Principle (SRP)

Every class should have one responsibility.

Good

```
CreateOrderAction
```

Bad

```
OrderService
- Create Order
- Update Order
- Cancel Order
- Print Receipt
- Send Email
- Generate Report
```

---

## 2. Fat Actions, Thin Components

Livewire Components should only handle:

- UI
- Validation
- Calling Actions

Business logic belongs inside Action classes.

Good

```php
CreateOrderAction::execute($data);
```

Bad

```php
public function save()
{
    // 300 lines of business logic
}
```

---

## 3. Keep Controllers Thin

Controllers should only:

- Receive Request
- Authorize
- Validate
- Call Action
- Return Response

Controllers should never contain business logic.

---

## 4. Business Logic Belongs in Actions

Every business operation should have a dedicated Action.

Examples

```
CreateProductAction

UpdateProductAction

AdjustStockAction

CreateOrderAction

CompleteOrderAction

CancelOrderAction

RecordExpenseAction

RecordOwnerWithdrawalAction
```

One Action = One Business Operation

---

## 5. Services Are Reusable

Use Services for reusable processes shared across modules.

Examples

```
InventoryService

FinanceService

NotificationService

DashboardService
```

Services should not perform complete business workflows.

Actions orchestrate Services.

---

# Livewire Standards

## Components

One component = One responsibility.

Examples

```
Products/Index

Products/Create

Products/Edit

Orders/Index

Orders/Show
```

Avoid components that perform multiple unrelated functions.

---

## Validation

Always validate user input.

Preferred

```php
$this->validate();
```

or dedicated Form Request / Validation classes.

Never trust browser input.

---

## Database Transactions

Wrap related operations in transactions.

Example

```
Create Order

↓

Insert Order

↓

Insert Order Items

↓

Deduct Stock

↓

Create Cash Transaction

↓

Create Activity Log

↓

Commit
```

If any step fails:

Rollback everything.

---

## Error Handling

Never expose raw exceptions to users.

Users should receive clear, friendly messages.

Example

Good

```
Unable to complete the order.

Please try again or contact the administrator.
```

Bad

```
SQLSTATE[23000] Integrity Constraint Violation...
```

---

## Logging

Unexpected exceptions should be logged.

Log:

- Exception
- User
- URL
- Request Data (excluding sensitive information)
- Stack Trace

---

# Database Standards

## Eloquent First

Prefer Eloquent relationships.

Good

```php
$order->items;
```

Avoid unnecessary manual joins.

---

## Avoid N+1 Queries

Always eager load relationships.

Good

```php
Order::with('items')->get();
```

Bad

```php
foreach ($orders as $order) {
    $order->items;
}
```

---

## Query Only What You Need

Good

```php
Product::select('id', 'product_name', 'selling_price')->get();
```

Avoid

```php
Product::all();
```

when unnecessary.

---

## Never Query in Blade

Good

Controller / Livewire

```php
$products = Product::all();
```

Blade

```blade
@foreach($products as $product)
```

Bad

```blade
{{ Product::count() }}
```

---

# Business Rules

Business rules belong in Actions.

Never inside Blade.

Never inside JavaScript.

Never duplicated across multiple components.

---

# Validation Rules

Validate:

- Required Fields
- Data Types
- Unique Values
- Numeric Limits
- Business Rules

Never rely solely on frontend validation.

---

# Authorization

Always authorize sensitive operations.

Examples

```
Create Product

Delete Product

Record Expense

Adjust Inventory

Update Settings
```

Use Policies or Gates.

Never trust hidden buttons.

---

# Soft Deletes

Archive records instead of deleting them.

Never permanently delete:

- Orders
- Expenses
- Cash Transactions
- Activity Logs
- Stock Movements

---

# Activity Logs

Every important business operation should create an Activity Log.

Examples

```
Product Created

Order Updated

Expense Recorded

Inventory Adjusted

Settings Updated
```

---

# Cash Transactions

Every financial operation should create a Cash Transaction.

Examples

```
Sale

Expense

Owner Withdrawal

Refund

Salary
```

Never modify cash directly.

---

# Inventory

Inventory should only change through Stock Movements.

Never update stock directly.

Correct

```
Adjust Stock

↓

Stock Movement

↓

Update Inventory

↓

Activity Log
```

---

# Constants

Avoid magic values.

Good

```php
OrderStatus::PENDING
```

Bad

```php
$status = 2;
```

---

# Enums

Use Enums for fixed values.

Examples

```
OrderStatus

PaymentStatus

UserRole

CashTransactionType

StockMovementType
```

Never hardcode status strings throughout the project.

---

# Configuration

Business values belong in configuration or database settings.

Avoid

```php
$lowStock = 5;
```

Better

```
settings.low_stock_threshold
```

---

# File Uploads

Always validate:

- File Type
- File Size
- MIME Type

Store uploaded files outside the public root when appropriate.

Never trust file extensions.

---

# Security

Never expose:

- Passwords
- Tokens
- API Keys
- Secrets

Never commit `.env` files.

Always hash passwords.

Escape user-generated content.

---

# Performance

Prefer pagination.

Avoid loading thousands of records.

Cache only when necessary.

Optimize queries before introducing caching.

---

# Testing

Every business-critical feature should have tests.

Priority:

- Order Creation
- Inventory Adjustment
- Expense Recording
- Cash Transactions
- User Permissions

---

# Git Workflow

Branch Naming

```
feature/products

feature/orders

feature/finance

fix/inventory

hotfix/login

refactor/dashboard
```

Commit Format

```
feat:

fix:

refactor:

docs:

style:

test:
```

Example

```
feat: implement inventory adjustment

fix: resolve duplicate cash transaction

docs: update finance module
```

---

# Code Review Checklist

Before merging:

- Code follows naming conventions
- Validation exists
- Authorization exists
- Activity Log created
- Cash Transaction created (if applicable)
- Stock Movement created (if applicable)
- No duplicated logic
- No debug statements
- No commented-out code
- Tests pass

---

# Development Checklist

Before implementing any feature, ask:

1. Does it solve a real business problem?
2. Is the code easy to understand?
3. Is validation complete?
4. Is authorization enforced?
5. Is every business action traceable?
6. Does money create a Cash Transaction?
7. Does inventory create a Stock Movement?
8. Can the feature scale without major refactoring?

If the answer is "No", redesign before implementation.

---

# Laravel Best Practices

- Use Route Model Binding.
- Prefer dependency injection.
- Use Eloquent relationships.
- Keep migrations atomic.
- Keep seeders idempotent.
- Use database transactions for multi-step operations.
- Use Policies for authorization.
- Prefer Enums over magic strings.
- Keep Livewire components focused on UI.
- Move business logic into Actions.

---

# Final Principle

Write code for the next developer.

That next developer may be:

- Yourself six months from now.
- A new team member.
- An AI coding assistant.

Good architecture is not measured by how clever the code is.

It is measured by how quickly someone can understand, trust, and safely extend it.