# 07_USER_FLOWS.md
# ALAS Business Manager - User Flow Specification
Version: 1.0

---

# Purpose

This document defines the official user workflows for ALAS Business Manager.

A workflow describes:

- Who performs an action
- What steps they take
- What the system should do
- What records should be created
- What business rules should be enforced

The purpose is to ensure that the system follows how ALAS actually operates.

---

# User Roles

ALAS Business Manager has three primary user types.

---

# Owner

The business owner.

Responsibilities:

- Monitor business health
- Review finances
- Make decisions
- Manage employees
- Review reports

Access:

Full system access.

---

# Manager

Operational controller.

Responsibilities:

- Manage products
- Manage inventory
- Manage orders
- Manage daily operations

Access:

Operational modules.

Finance access depends on permission.

---

# Staff

Daily operator.

Responsibilities:

- Process orders
- Update order status
- Perform assigned tasks

Access:

Limited operational features.

---

# General Application Flow

```
Login

↓

Dashboard

↓

Select Business Operation

↓

Perform Action

↓

Validate Rules

↓

Create Records

↓

Create Activity Log

↓

Update Dashboard

↓

Notify Relevant Users
```

---

# FLOW 01 - User Login

## Goal

Allow authorized users to access the system.

---

## Steps

```
User opens application

↓

Enter email and password

↓

System validates credentials

↓

System checks account status

↓

System creates session

↓

Redirect to Dashboard
```

---

## System Actions

Create:

```
Activity Log

Action:
User Logged In
```

---

## Validation

User cannot login if:

- Account is inactive
- Password is incorrect
- User does not exist

---

# FLOW 02 - Dashboard Overview

## Goal

Allow the owner to understand business status within 30 seconds.

---

## Dashboard Information

Display:

```
Available Business Funds

Today's Sales

Today's Expenses

Pending Orders

Orders To Pack

Low Stock Products

Recent Activities
```

---

## Dashboard Rules

Dashboard should prioritize:

1. Money
2. Orders
3. Inventory
4. Activities

---

## Example

Owner logs in.

System shows:

```
Available Funds:
$5,000

Orders Pending:
12

Low Stock:
3 Products

Today's Sales:
$850
```

Owner immediately knows what needs attention.

---

# FLOW 03 - Product Creation

## Goal

Create a new clothing product.

---

## User

Owner

Manager

---

## Steps

```
Products Page

↓

Click Create Product

↓

Enter Product Information

↓

Save

↓

System Validates

↓

Product Created

↓

Inventory Created

↓

Activity Logged
```

---

## Required Information

```
Product Name

SKU

Selling Price

Cost Price

Initial Stock
```

---

## System Creates

Product:

```
products
```

Inventory:

```
inventories
```

Activity:

```
Product Created
```

---

# FLOW 04 - Inventory Management

## Goal

Maintain accurate stock levels.

---

# Adding Stock

Example:

New supplier delivery arrives.

---

## Steps

```
Inventory Page

↓

Select Product

↓

Add Stock

↓

Enter Quantity

↓

Enter Reason

↓

Confirm

↓

Create Stock Movement

↓

Update Inventory

↓

Create Activity Log
```

---

## Records Created

Stock Movement:

```
Type:
Stock Addition
```

Inventory:

```
Quantity Increased
```

Activity:

```
Stock Added
```

---

# Stock Adjustment

Used for:

- Damaged items
- Lost items
- Counting correction

---

## Flow

```
Select Product

↓

Adjust Stock

↓

Enter Reason

↓

Confirm

↓

Create Stock Movement

↓

Update Inventory

↓

Log Activity
```

---

# FLOW 05 - Low Stock Alert

## Goal

Prevent running out of products.

---

## Trigger

System checks:

```
Current Stock <= Minimum Stock
```

---

## System Actions

Create:

```
Notification
```

Example:

```
ALAS Oversized Tee Black Large
is below minimum stock.
```

---

# FLOW 06 - Creating Customer Order

## Goal

Record customer purchase.

---

## User

Owner

Manager

Staff

---

## Steps

```
Orders

↓

Create Order

↓

Select Products

↓

Enter Quantity

↓

Review Total

↓

Save Order
```

---

## System Validation

Check:

- Product is active
- Stock is available
- Quantity is valid

---

## System Creates

```
Order

Order Items

Activity Log
```

---

# FLOW 07 - Order Status Management

## Goal

Track order progress.

---

# Status Flow

```
Pending

↓

Confirmed

↓

Packed

↓

Shipped

↓

Completed
```

---

# Pending

Initial order state.

---

# Confirmed

Customer order accepted.

---

# Packed

Items prepared.

Shipping information is optional.

---

# Shipped

Requires:

```
Shipping Details
```

Exception:

```
Delivery Method = Meetup
```

---

# Completed

Requires:

```
Payment Completed
```

System creates:

```
Cash Transaction

Activity Log
```

---

# Cancelled Order

Flow:

```
Cancel Order

↓

Enter Reason

↓

Restore Reserved Stock

↓

Create Activity Log
```

---

# FLOW 08 - Meetup Order

## Goal

Support customers who do not require shipping.

---

## Steps

```
Create Order

↓

Select Delivery Method

↓

Choose Meetup

↓

Enter Meetup Details

↓

Complete Order
```

---

## Required

```
Meetup Date

Location
```

Shipping fields are optional.

---

# FLOW 09 - Recording Expense

## Goal

Track business spending.

---

## User

Owner

Manager

---

## Steps

```
Finance

↓

Create Expense

↓

Select Category

↓

Enter Amount

↓

Enter Description

↓

Save
```

---

## System Creates

Expense:

```
expenses
```

Cash:

```
cash_transactions
```

Activity:

```
Expense Recorded
```

---

# FLOW 10 - Owner Withdrawal

## Goal

Record money taken by owner.

---

## Important Rule

Owner withdrawal is NOT an expense.

---

## Flow

```
Finance

↓

Owner Withdrawal

↓

Enter Amount

↓

Confirm

↓

Create Cash Transaction

↓

Create Activity Log
```

---

# FLOW 11 - Financial Review

## Goal

Allow owner to understand money movement.

---

## Questions Dashboard Should Answer

```
Where did money go?

How much did we earn?

How much did we spend?

How much can safely be used?

How much has owner withdrawn?
```

---

# FLOW 12 - User Management

## Goal

Manage employees.

---

## Steps

```
Users

↓

Create User

↓

Assign Role

↓

Save

↓

Activity Log
```

---

## Permissions

Owner:

Full access

Manager:

Operational access

Staff:

Assigned permissions

---

# FLOW 13 - Activity Monitoring

## Goal

Maintain accountability.

---

## Owner Can View

```
Who changed inventory?

Who created orders?

Who recorded expenses?

Who changed settings?
```

---

# FLOW 14 - Notification Handling

## Notification Sources

Inventory:

```
Low Stock
```

Orders:

```
Order Requires Action
```

Finance:

```
Important Financial Event
```

System:

```
Security Event
```

---

# FLOW 15 - Daily Business Routine

Recommended daily workflow:

```
Login

↓

Review Dashboard

↓

Check Orders

↓

Check Inventory

↓

Process Customer Orders

↓

Record Expenses

↓

Review Cash Position

↓

Logout
```

---

# FLOW 16 - End Of Month Review

Owner workflow:

```
Open Reports

↓

Review Sales

↓

Review Expenses

↓

Review Profit

↓

Review Inventory

↓

Review Cash Transactions

↓

Make Business Decisions
```

---

# Development Mapping

Each workflow should become:

```
Workflow

↓

Livewire Component

↓

Action Class

↓

Service (if needed)

↓

Database Transaction

↓

Activity Log

↓

Notification
```

---

# Implementation Rule

Before creating a feature:

Define:

1. User
2. Goal
3. Steps
4. Validation
5. Database Changes
6. Activity Log
7. Notifications

No feature should be implemented without a defined workflow.

---

# Final Principle

ALAS Business Manager should follow the business process, not force the business to follow the software.

The system exists to make operations clear, money visible, inventory accurate, and every action accountable.