# 05_UI_UX_GUIDELINES.md
# ALAS Business Manager - UI/UX Design Guidelines
Version: 1.0

---

# Purpose

This document defines the official User Interface (UI) and User Experience (UX) standards for ALAS Business Manager.

The goal is to ensure the application remains:

- Simple
- Modern
- Consistent
- Fast
- Easy to learn
- Easy to navigate

This document should be followed for every new page, component, and feature.

---

# Design Philosophy

ALAS Business Manager is built for business owners and employees, not developers.

The system should feel effortless to use.

A first-time employee should be able to complete common tasks with little or no training.

Every screen should answer one question:

> "What is the next thing the user needs to do?"

---

# Design Principles

## Simplicity First

Avoid unnecessary information.

If something does not help the user complete a task, remove it.

---

## Reduce Clicks

Common tasks should require as few clicks as possible.

Examples

Good

Dashboard
→ New Order

Dashboard
→ Record Expense

Dashboard
→ Adjust Stock

Bad

Dashboard
→ Inventory
→ Products
→ Search
→ Select Product
→ Adjust

---

## Consistency

Every page should follow the same layout.

Users should never wonder where to find:

- Save
- Cancel
- Search
- Filters
- Actions

---

## Readability

Information should be easy to scan.

Use whitespace generously.

Avoid crowded interfaces.

---

## Business First

Every page should help users operate the business.

Avoid decorative elements that distract from the workflow.

---

# Target Users

The interface should be understandable by:

- Owner
- Manager
- Staff

No technical knowledge should be required.

---

# Design Reference

The official UI design is maintained separately.

The implementation **must follow the approved design** before introducing new layouts or components.

Provide one or more of the following references:

## Stitch AI Project

```
https://stitch.withgoogle.com/...
```

or

## Figma Project

```
https://figma.com/...
```

or

## Design Screenshots

Store screenshots inside the project.

Example

```
docs/

design/

dashboard.png

products.png

inventory.png

orders.png

finance.png
```

These files become the visual source of truth.

If there is a conflict between implementation and the approved design, the approved design takes precedence unless a business requirement changes.

---

# Color Palette

Primary Background

```
White
```

Secondary Background

```
Light Gray
```

Cards

```
White
```

Primary Text

```
Dark Gray / Almost Black
```

Secondary Text

```
Gray
```

Success

```
Green
```

Warning

```
Orange
```

Danger

```
Red
```

Info

```
Blue
```

Avoid excessive colors.

Color should communicate status, not decoration.

---

# Typography

Use a clean sans-serif font.

Examples

- Inter
- Geist
- Instrument Sans

Headings

Bold

Body

Regular

Never use decorative fonts.

---

# Layout

Every page should follow:

```
Sidebar

↓

Top Navigation

↓

Page Header

↓

Action Buttons

↓

Filters

↓

Content

↓

Pagination
```

---

# Sidebar

Contains only major modules.

Dashboard

Products

Inventory

Orders

Finance

Reports

Users

Settings

Avoid nested menus unless necessary.

---

# Dashboard

The owner should understand the business within 30 seconds.

Show only important information.

Examples

- Current Cash
- Today's Sales
- Today's Expenses
- Pending Orders
- Orders to Pack
- Low Stock Alerts
- Recent Activities
- Notifications

Avoid information overload.

---

# Cards

Cards should have:

- Rounded corners
- Soft shadow
- Consistent spacing
- Clear title

Do not overcrowd cards.

---

# Tables

Every table should support:

- Search
- Sorting
- Pagination
- Row Actions

Recommended Actions

View

Edit

Delete

Archive

Keep action buttons consistent across all modules.

---

# Forms

Forms should be simple.

Group related fields.

Use labels.

Show validation messages immediately.

Buttons

Primary

```
Save
```

Secondary

```
Cancel
```

Danger

```
Delete
```

Avoid long forms when possible.

---

# Search

Every searchable module should include search.

Examples

Products

Orders

Expenses

Users

Search should be fast and responsive.

---

# Filters

Keep filters simple.

Examples

Status

Date

Category

Payment Status

Avoid excessive filter options.

---

# Buttons

Primary

Solid

Secondary

Outlined

Danger

Red

Disabled

Gray

Button labels should describe actions.

Good

```
Create Order

Record Expense

Adjust Stock
```

Avoid

```
Submit

Execute

Process
```

---

# Icons

Use icons only to reinforce meaning.

Do not rely solely on icons.

Always include labels when possible.

---

# Status Badges

Use consistent colors.

Pending

Orange

Completed

Green

Cancelled

Red

Draft

Gray

---

# Notifications

Notifications should:

- Be concise
- Explain what happened
- Suggest the next action

Example

Good

```
Inventory for ALAS Oversized Tee (Black / Medium) is below the minimum stock level.
```

Bad

```
Inventory Error
```

---

# Empty States

Never leave blank pages.

Examples

"No products found."

"Create your first product."

"No orders today."

Provide a clear call-to-action.

---

# Confirmation Dialogs

Require confirmation for destructive actions.

Examples

Delete Product

Archive User

Cancel Order

Delete Expense

Confirmation should clearly explain the consequence.

---

# Responsive Design

Support:

Desktop

Tablet

Mobile

Primary optimization target:

Desktop

The layout should remain usable on smaller screens.

---

# Accessibility

Use sufficient color contrast.

Every input should have a label.

Keyboard navigation should work.

Clickable areas should be large enough.

Do not communicate information using color alone.

---

# Loading States

Always provide feedback.

Examples

Loading spinner

Skeleton loaders

Disabled buttons while saving

Never leave users wondering if something is happening.

---

# Error Messages

Use human language.

Good

```
Unable to save the product.

Please check the required fields.
```

Avoid

```
SQLSTATE[23000]
```

---

# Success Messages

Provide immediate feedback.

Examples

```
Product created successfully.

Order completed successfully.

Expense recorded successfully.
```

---

# Page Structure Standard

Every page should include:

1. Page Title

2. Short Description (optional)

3. Primary Action Button

4. Search

5. Filters

6. Main Content

7. Pagination

Maintain this layout throughout the application.

---

# UI Components

Reusable components should include:

- Buttons
- Cards
- Tables
- Inputs
- Select Boxes
- Text Areas
- Date Picker
- Status Badge
- Alert Banner
- Notification Dropdown
- Confirmation Modal
- Empty State
- Loading Skeleton

Avoid duplicating UI components.

---

# Design Consistency

Before implementing a new screen:

- Check existing pages.
- Reuse existing components.
- Maintain spacing.
- Maintain typography.
- Maintain colors.

Never redesign a page unless the entire design system changes.

---

# Developer Guidelines

Before building a page:

1. Review the approved Stitch AI or Figma design.
2. Follow the existing component library.
3. Match spacing, typography, and layout.
4. Reuse components whenever possible.
5. Validate responsiveness.

Do not "improvise" the interface without approval.

---

# Future Improvements

Potential future enhancements:

- Dark Mode
- Theme Customization
- Multi-language Support
- Keyboard Shortcuts
- Advanced Dashboard Widgets
- Drag-and-Drop Dashboard
- Saved Table Views

These features should not compromise simplicity.

---

# Final Principle

The best interface is one that users do not need to think about.

ALAS Business Manager should allow users to focus on running the business—not learning the software.

Every design decision should reduce confusion, reduce clicks, and improve confidence.