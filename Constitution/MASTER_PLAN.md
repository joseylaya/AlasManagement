# ALAS Business Manager
## Master Development Plan (Version 1.0)

---

# Vision

ALAS Business Manager is a lightweight internal business management system built specifically for ALAS Clothing.

It is **not an ERP**.

Its purpose is to solve the actual problems experienced by the business today:

- Poor cash handling
- Inventory tracking
- Order management
- Employee accountability
- Lack of business visibility

The system should remain simple, fast, intuitive, and easy for non-technical employees.

The owner should understand the business within **30 seconds** of logging in.

---

# Core Principles

## Simplicity First

Every feature must solve a real business problem.

Avoid enterprise complexity.

If a feature does not improve daily operations, do not build it.

---

## Traceability

Every important action must answer:

- Who did it?
- What changed?
- When did it happen?

Every record stores:

- created_by
- updated_by
- created_at
- updated_at

Soft delete should be used whenever appropriate.

---

## Money First

Never make decisions based solely on the bank balance.

Instead, focus on the business's available cash after commitments.

Every movement of money must be recorded.

Examples:

- Sale
- Expense
- Owner Withdrawal
- Salary
- Refund

Cash visibility is the heart of the system.

---

## Inventory Accuracy

Inventory should always reflect reality.

Every stock change must create a Stock Movement.

Examples:

- Purchase
- Sale
- Adjustment
- Damage
- Return

Stock should never be manually edited without leaving a history.

---

## User Accountability

Every employee has their own account.

All actions are recorded through Activity Logs.

The owner should always know:

- Who created an order
- Who modified stock
- Who changed a product
- Who recorded an expense

---

# Technology Stack

Backend

- Laravel
- Livewire

Database

- MySQL

Frontend

- Blade
- Livewire
- Tailwind CSS

Architecture

- Simple modular architecture
- Service-based business logic
- No unnecessary microservices

---

# Design Philosophy

Modern

Minimal

Clean

White background

Soft shadows

Large spacing

Easy navigation

Mobile-friendly

Designed for non-technical employees.

The interface should require little to no training.

---

# Core Modules

## Dashboard

Purpose

Provide a complete overview of the business within 30 seconds.

Display:

- Current Cash
- Available Business Funds
- Today's Sales
- Today's Expenses
- Pending Orders
- Orders to Pack
- Meet-ups Today
- Low Stock Alerts
- Recent Activities
- Notifications
- Quick Actions

---

## Products

Manage products and variants.

Support:

- Name
- SKU
- Category
- Color
- Size
- Selling Price
- Cost Price
- Product Images
- Active Status

Each product may have multiple variants.

---

## Inventory

Manage stock.

Features

- Current Stock
- Stock Movement History
- Low Stock Alert
- Inventory Adjustment
- Damaged Items

Dashboard should notify low stock automatically.

---

## Sales

Manage customer orders.

Order Status

Pending

Confirmed

Packed

Shipped

Completed

Cancelled

Support:

Shipping

Meet-up

Payment Status

Pending

Paid

Refunded

Each order stores a snapshot of product pricing.

---

## Finance

Track all cash movement.

Income

Expenses

Owner Withdrawals

Salary Payments

Refunds

Display

Current Cash

Today's Income

Today's Expenses

Monthly Profit

Available Business Funds

This module directly solves the company's cash handling problems.

---

## Reports

Provide meaningful reports.

Examples

Sales Summary

Inventory Summary

Expense Summary

Profit Summary

Best Selling Products

Low Stock Report

Cash Flow

Owner Withdrawals

Reports should answer business questions, not simply display raw data.

---

## Users

Support multiple employees.

Roles

Owner

Manager

Staff

Each user has:

Login

Role

Permissions

Activity History

---

## Activity Logs

Record every important system action.

Examples

Created Product

Updated Product

Adjusted Inventory

Created Order

Updated Order

Recorded Expense

Changed Settings

Logs should never be deleted.

---

## Notifications

Automatic alerts.

Examples

Low Stock

Order Ready to Pack

Expense Recorded

Cash Running Low

Notifications should guide users toward the next action.

---

## Settings

Manage configurable values.

Examples

Expense Categories

Product Categories

Payment Methods

Company Information

Low Stock Threshold

System Preferences

No code changes should be required for normal business configuration.

---

# Suggested Database (Version 1)

users

products

product_variants

stocks

stock_movements

orders

order_items

expenses

expense_categories

cash_transactions

owner_drawals

activity_logs

notifications

settings

Approximately 14 core tables.

---

# Cash Transactions

Instead of a complex accounting ledger, Version 1 uses a Cash Transactions table.

Every financial event creates a cash transaction.

Examples

+ Sale

- Expense

- Owner Withdrawal

- Salary

- Refund

This provides a clear history of where money comes from and where it goes.

---

# Development Commandments

Before implementing any feature, ask:

1. Does it solve a real business problem?
2. Does it improve business visibility?
3. Is every important action traceable?
4. Does every cash movement create a Cash Transaction?
5. Does every inventory change create a Stock Movement?
6. Can a non-technical employee understand it?
7. Can the owner understand the business within 30 seconds?
8. Will this still work with 10 employees and thousands of monthly orders?

If the answer is **No**, redesign the feature.

---

# Long-Term Philosophy

Build only what the business needs today.

Keep the system simple.

Reduce manual work.

Protect business cash.

Track inventory accurately.

Make every action accountable.

Allow the system to grow naturally with ALAS Clothing without introducing unnecessary complexity.

The goal is not to build the biggest system.

The goal is to build the most useful system.

# Progressive Web App

The system architecture should remain compatible with Progressive Web App (PWA) implementation.

capabilities may include:

- Installable on desktop and mobile
- Push notifications