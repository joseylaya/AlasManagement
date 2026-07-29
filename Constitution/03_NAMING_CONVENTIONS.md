# 03_NAMING_CONVENTIONS.md
# ALAS Business Manager - Naming Conventions
Version: 1.0

---

# Purpose

This document defines the official naming conventions used throughout ALAS Business Manager.

Consistent naming makes the project easier to understand, maintain, and scale.

Every developer, AI assistant, and future contributor should follow these conventions.

---

# General Principles

Names should be:

- Clear
- Predictable
- Descriptive
- Consistent

Avoid abbreviations unless they are industry standard.

Good

```
product_name

selling_price

stock_quantity
```

Avoid

```
prd_nm

sell_prc

stk_qty
```

---

# Database

## Tables

Use:

- lowercase
- plural
- snake_case

Examples

```
users

products

inventories

stock_movements

orders

order_items

expenses

expense_categories

cash_transactions

activity_logs

notifications

settings
```

Never use

```
User

Product

tbl_products

productTable
```

---

## Columns

Use:

- lowercase
- snake_case

Examples

```
product_name

selling_price

cost_price

current_stock

order_number

payment_status

created_at
```

Avoid

```
ProductName

productName

Product_Name
```

---

## Primary Key

Every table uses

```
id
```

Never

```
product_id (as primary key)

userID

pk_id
```

---

## Foreign Keys

Always end with `_id`

Examples

```
user_id

product_id

order_id

expense_category_id

created_by

updated_by
```

---

## Timestamps

Laravel standard

```
created_at

updated_at

deleted_at
```

Never rename these.

---

## Status Fields

Always use

```
status
```

instead of

```
is_active

enabled

disabled

visible
```

Example

```
draft

pending

active

completed

cancelled

archived
```

Status values should be lowercase.

---

# Laravel

## Models

Singular

PascalCase

Examples

```
User

Product

Inventory

Order

Expense

CashTransaction

ActivityLog
```

---

## Controllers

Suffix with Controller

Examples

```
ProductController

OrderController

ExpenseController
```

---

## Livewire Components

Folder

Plural

```
Products/

Orders/

Inventory/

Finance/
```

Pages

```
Index

Create

Edit

Show
```

Example

```
Products/Index

Products/Create

Products/Edit

Orders/Show
```

---

## Services

Suffix

Service

Examples

```
InventoryService

OrderService

FinanceService
```

Services contain reusable business logic.

---

## Actions

Suffix

Action

Examples

```
CreateOrderAction

AdjustStockAction

RecordExpenseAction

CompleteOrderAction
```

Actions perform one business operation.

---

## Policies

Suffix

Policy

Examples

```
ProductPolicy

OrderPolicy

ExpensePolicy
```

---

## Form Requests

Suffix

Request

Examples

```
StoreProductRequest

UpdateProductRequest

StoreExpenseRequest
```

---

# Routes

Use plural resource names.

```
products

inventory

orders

finance

reports

settings
```

Examples

```
/products

/products/create

/orders

/orders/15

/settings
```

---

# Variables

camelCase

Examples

```
$product

$order

$currentStock

$totalSales

$availableCash
```

Collections

Plural

```
$products

$orders

$expenses
```

Booleans

Prefix

```
$isActive

$isPaid

$isCancelled

$hasStock

$canEdit
```

---

# Constants

UPPER_SNAKE_CASE

Examples

```
DEFAULT_LOW_STOCK

MAX_LOGIN_ATTEMPTS

DEFAULT_PAGE_SIZE
```

---

# Enums

PascalCase

Examples

```
OrderStatus

PaymentStatus

StockMovementType

CashTransactionType

UserRole
```

Enum values

```
pending

confirmed

packed

shipped

completed

cancelled
```

---

# Files

Blade

kebab-case

```
index.blade.php

create.blade.php

edit.blade.php

show.blade.php
```

---

Migration

Laravel default

```
2026_08_01_000001_create_products_table.php
```

---

Seeder

PascalCase

```
ProductSeeder

UserSeeder

ExpenseCategorySeeder
```

---

Factory

PascalCase

```
ProductFactory

OrderFactory
```

---

# Images

Products

```
products/

products/oversized-black-l-front.jpg

products/oversized-black-l-back.jpg
```

Users

```
users/profile.jpg
```

---

# Order Numbers

Format

```
ORD-000001
```

Examples

```
ORD-000001

ORD-000002
```

---

# Product SKU

Recommended format

```
ALAS-OS-BLK-L

ALAS-OS-WHT-M

ALAS-HWT-BLK-XL
```

Pattern

```
Brand-Design-Color-Size
```

---

# Cash Transaction Numbers

```
CTX-000001
```

---

# Expense Numbers

```
EXP-000001
```

---

# Activity Log Numbers

```
ACT-000001
```

---

# Notification Numbers

```
NOT-000001
```

---

# Methods

Method names should clearly describe the action.

Examples

```
createOrder()

completeOrder()

adjustStock()

recordExpense()

recordOwnerWithdrawal()

archiveProduct()

restoreProduct()
```

Avoid

```
save()

process()

execute()

run()
```

unless the context is obvious.

---

# Database Index Names

Laravel default naming is acceptable.

Examples

```
products_status_index

orders_user_id_index

expenses_created_at_index
```

---

# Permission Naming

Format

```
module.action
```

Examples

```
products.view

products.create

products.update

products.delete

orders.create

orders.update

finance.view

finance.manage

reports.export

settings.manage
```

---

# Notification Keys

Format

```
inventory.low_stock

sales.order_completed

finance.expense_recorded

system.backup_completed
```

---

# Activity Log Actions

Use past tense.

Examples

```
Product Created

Product Updated

Stock Adjusted

Expense Recorded

Order Completed

User Logged In

Settings Updated
```

---

# Git Branches

Feature

```
feature/products

feature/orders

feature/dashboard
```

Bug Fix

```
fix/order-status

fix/inventory-adjustment
```

Hotfix

```
hotfix/payment-status
```

Refactor

```
refactor/finance-service
```

---

# Commit Messages

Format

```
type: description
```

Examples

```
feat: add product inventory management

fix: resolve stock deduction issue

refactor: simplify order service

docs: update database specification

style: improve dashboard layout

test: add order feature tests
```

---

# Final Principle

Naming is part of the system architecture.

Good names reduce bugs, improve readability, and make onboarding easier.

If a name requires explanation, choose a better name.

Every identifier should clearly communicate its purpose without relying on comments.