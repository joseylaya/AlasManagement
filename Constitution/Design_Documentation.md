# Design Documentation
# ALAS Business Manager

Version: 1.0

---

# Purpose

This directory contains the official UI/UX design system documentation for ALAS Business Manager.

The purpose of this documentation is to ensure that:

- Developers implement the correct interface.
- AI coding assistants generate consistent UI.
- Future team members understand the design decisions.
- New features follow the existing visual language.

The design documentation is part of the system architecture.

UI decisions are treated as business decisions because they directly affect employee productivity and system usability.

---

# Design Source of Truth

ALAS Business Manager has one official visual source of truth.

Priority order:

```
1. Approved Stitch AI Design

2. Approved Design Screenshots

3. Component Documentation

4. Developer Implementation
```

If implementation conflicts with the approved design:

The design must be reviewed first.

Developers should not create new UI patterns without approval.

---

# Primary Design Source

## Stitch AI Project

```
https://stitch.withgoogle.com/u/1/projects/4283101181864744297?pli=1
```

Purpose:

- Screen layouts
- Component appearance
- Spacing
- Typography
- Colors
- User interaction references

---

# Secondary Design Sources

## Figma

Optional.

```
<Figma URL>
```

---

# Local Design Repository

All exported design references should exist inside:

```
docs/

design/

├── README.md
│
├── screens/
│
├── components/
│
├── assets/
│
└── flows/
```

---

# Directory Explanation


## screens/

Contains complete page references.

Example:

```
dashboard.png

products.png

inventory.png

orders.png

finance.png

reports.png

settings.png
```


## components/

Contains reusable UI references.

Example:

```
button.png

card.png

table.png

modal.png

form.png

badge.png
```


## assets/

Contains design resources.

Example:

```
logo.svg

icons/

illustrations/

empty-states/
```


## flows/

Contains user journey references.

Example:

```
create-order-flow.png

inventory-adjustment-flow.png

expense-record-flow.png
```

---

# Design Philosophy

ALAS Business Manager is designed for:

- Business owners
- Managers
- Employees

Users should not need technical knowledge.

The interface should feel:

- Simple
- Modern
- Clean
- Professional
- Predictable

The system should help employees complete tasks, not make them learn software.

---

# Core UX Principles


## 1. Simplicity Over Features

A simple workflow is better than a powerful but confusing workflow.


## 2. Reduce User Decisions

The system should guide users.

Avoid asking users unnecessary questions.


## 3. Show Important Information First

Users should immediately understand:

- What needs attention?
- What action should be taken?
- What changed?


## 4. Consistency

The same action should always look and behave the same.

Examples:

Save button

Delete action

Search

Filter

Pagination

Notifications

---

# Design Language

## Visual Style

Primary style:

```
Modern Business Application
```

Characteristics:

- White background
- Soft shadows
- Clean cards
- Minimal colors
- Spacious layout
- Clear hierarchy


---

# Color Tokens


## Background

```
White
```


## Secondary Background

```
Light Gray
```


## Primary Text

```
Dark Gray
```


## Secondary Text

```
Gray
```


## Success

```
Green
```


## Warning

```
Orange
```


## Danger

```
Red
```


## Information

```
Blue
```

Colors must communicate meaning.

Do not use colors only for decoration.

---

# Typography


Recommended:

```
Inter

Geist

Instrument Sans
```

Rules:

- Clear hierarchy
- Comfortable spacing
- Avoid excessive font sizes
- Prioritize readability

---

# Component System

Every reusable component must have:

- Design reference
- Usage purpose
- States
- Responsive behavior


Example:

Button Component

States:

```
Default

Hover

Loading

Disabled

Danger
```

---

# Required Components


## Navigation

```
Sidebar

Top Navigation

Breadcrumbs
```


## Data Display

```
Cards

Tables

Charts

Badges
```


## Input

```
Text Input

Select

Date Picker

Search

Filter
```


## Feedback

```
Notification

Alert

Modal

Toast

Empty State

Loading State
```

---

# Screen Design Standards


Every screen should define:

```
Page Purpose

Primary User Action

Required Data

Available Actions

Empty State

Loading State

Error State
```

---

# Current Screens


## Dashboard

Purpose:

Provide business visibility within 30 seconds.

Information:

- Available Business Funds
- Sales
- Expenses
- Pending Orders
- Low Stock Alerts
- Recent Activities


---

## Products

Purpose:

Manage clothing catalog.

Actions:

- Create Product
- Update Product
- Archive Product


---

## Inventory

Purpose:

Monitor stock.

Actions:

- View Stock
- Add Stock
- Adjust Stock


---

## Orders

Purpose:

Manage customer purchases.

Actions:

- Create Order
- Update Status
- Complete Order
- Cancel Order


---

## Finance

Purpose:

Control money movement.

Actions:

- Record Expense
- View Cash Transactions
- Review Financial History


---

# UI States


Every page must support:


## Loading State

Example:

Skeleton loading.


## Empty State

Example:

"No products found."

Provide next action.


## Error State

Example:

"Unable to load orders."


## Success State

Example:

"Order completed successfully."

---

# Permission-Based UI

The interface must respect user roles.

Example:

Owner:

Can see finance.


Staff:

Cannot see financial information.


Do not only hide buttons.

Backend authorization must still exist.

---

# Design To Development Workflow


Before coding:

1. Review Stitch AI design.
2. Confirm screen exists.
3. Confirm components exist.
4. Check business rules.
5. Implement using existing components.


After coding:

1. Compare with design.
2. Verify responsiveness.
3. Verify user flow.
4. Update screenshots if approved.


---

# AI Development Instructions


When using:

- Claude Sonnet
- ChatGPT
- GitHub Copilot


Provide:

```
MASTER_PLAN.md

05_UI_UX_GUIDELINES.md

docs/design/README.md

Stitch AI link

Relevant screenshots
```


AI must:

- Follow existing design.
- Reuse components.
- Avoid creating new patterns.
- Follow Laravel Livewire structure.
- Follow business rules.

---

# Design Change Management


Any design change requires:

1. Update Stitch AI.
2. Export screenshots.
3. Update documentation.
4. Increase version number.
5. Record changelog.


---

# Design Changelog


## Version 1.0

Initial design system.

Included:

- Dashboard
- Products
- Inventory
- Orders
- Finance
- Reports
- Users
- Settings


---

# Final Principle


The design is not decoration.

The design is the bridge between the business and the system.

A good interface allows ALAS employees to work faster, make fewer mistakes, and operate the business confidently.

Every new screen should feel like it has always belonged to ALAS Business Manager.


# Progressive Web App

The system architecture should remain compatible with Progressive Web App (PWA) implementation.

capabilities may include:

- Installable on desktop and mobile
- Push notifications