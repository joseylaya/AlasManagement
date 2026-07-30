# ALAS Business Manager — Mobile UI/UX and Role Access Redesign

Improve the existing ALAS Business Manager design with two priorities:

1. Fix the mobile user experience, especially pages containing large tables.
2. Apply clear role-based access for Owner, Manager, and Staff.

Do not redesign the entire application. Preserve the existing modern white theme, soft shadows, rounded cards, typography, navigation style, and overall visual identity.

---

# 1. Mobile UI/UX Improvements

REMOVE THE BLACK THEME FOR NOW

The current desktop tables do not work well on mobile devices. They contain too many columns, require excessive horizontal scrolling, and make actions difficult to use.

Do not simply shrink desktop tables for mobile.

Create a mobile-specific presentation that prioritizes readability and common actions.

## Mobile Table Behavior

For screens below 768px:

* Replace wide tables with stacked cards or compact list rows.
* Show only the most important information initially.
* Move secondary information into expandable sections.
* Keep primary actions visible and easy to tap.
* Avoid horizontal scrolling whenever possible.
* Use full-width search and filter controls.
* Use bottom sheets, drawers, or modals for filters and secondary actions.
* Use pagination or a “Load More” interaction.
* Keep touch targets at least 44px high.
* Use sticky primary actions where appropriate.
* Display status using clear badges.
* Use icons together with text labels for important actions.

## Example Mobile Order Card

Display:

* Order number
* Customer name
* Total amount
* Payment status
* Order approval status
* Fulfillment method
* Order date
* Assigned staff
* Primary action

Secondary information may appear after tapping “View Details.”

Example:

```text
ORD-000124
Juan Dela Cruz

₱1,700
Paid

Pending Approval
Meet-up

Created by: Maria
July 30, 2026

[View Order]
```

## Example Mobile Product Card

Display:

* Product image
* Product name
* SKU
* Color and size
* Selling price
* Available stock
* Low-stock status
* Primary action

## Example Mobile Finance Card

Display:

* Transaction type
* Description
* Amount
* Date
* Recorded by
* Status
* Primary action

Use clear positive and negative amount indicators, but do not rely on color alone.

## Responsive Navigation

Desktop:

* Persistent left sidebar

Tablet:

* Collapsible sidebar

Mobile:

* Drawer navigation or compact bottom navigation
* Keep the notification icon and user profile accessible
* Hide menu items the current user is not authorized to access

## Mobile Forms

* Use a single-column layout.
* Use large inputs and readable labels.
* Keep validation messages directly below the affected fields.
* Use a sticky bottom action area for Save and Cancel when forms are long.
* Break long forms into logical sections.
* Avoid showing too many fields at once.
* Use searchable selectors for products and users.
* Show order totals continuously while creating an order.

---

# 2. Role-Based Access Control

The interface and backend permissions must follow the same rules.

Do not rely only on hiding buttons. Unauthorized operations must also be blocked by Laravel policies, middleware, or permission checks.

There are three primary roles:

* Owner
* Manager
* Staff

---

# Owner Role

The Owner has complete access to the application.

The Owner can:

* Access every module
* View all financial data
* Create, edit, approve, cancel, archive, and restore records
* Approve or reject orders
* Manage products
* Manage inventory
* Adjust stock
* Manage expenses
* Manage cash transactions
* Record owner withdrawals
* Manage users and roles
* View all activity logs
* View and export all reports
* Manage system settings
* Override restricted actions when necessary
* Access sensitive business information
* Manage permissions
* Perform corrections and administrative actions

The Owner dashboard should display the complete business overview.

---

# Manager Role

The Manager controls daily business operations and may manage business money.

The Manager can:

* View the operational dashboard
* Create and manage orders
* Approve or reject orders
* Change approved order statuses
* Cancel orders when authorized
* Manage products
* Manage inventory
* Add stock
* Record inventory adjustments
* View low-stock alerts
* Manage expenses
* Record salary payments
* Record operational cash transactions
* Review financial activity
* View financial reports
* Manage order fulfillment
* Manage shipping and meet-up details
* View activity logs
* Review staff activity
* Export operational and financial reports when permitted
* Handle refunds when authorized

The Manager cannot:

* Manage the Owner account
* Change global system settings
* Change role permissions
* Create or promote another Owner
* Permanently delete historical records
* Perform owner withdrawals unless explicitly permitted
* Override protected owner-only controls

The Manager dashboard should focus on:

* Orders requiring approval
* Pending fulfillment
* Low-stock products
* Expenses
* Cash position
* Staff activity
* Notifications requiring action

---

# Staff Role

The Staff role is intended for day-to-day order entry and visibility.

The Staff can:

* Access a simplified dashboard
* Create customer orders
* Add products and quantities to an order
* Enter customer information
* Select shipping or meet-up fulfillment
* Record payment information
* Save an order for approval
* View orders
* View the status of orders
* View activity logs in read-only mode
* Open the Finance module in read-only mode
* View permitted cash transactions and expenses
* View products
* View inventory availability
* View notifications assigned to them

The Staff cannot:

* Approve or reject orders
* Change an order from Pending Approval to Approved
* Complete protected order transitions
* Cancel approved orders
* Modify financial transactions
* Record owner withdrawals
* Approve expenses
* Adjust inventory
* Change product prices
* Manage users
* Manage roles or permissions
* Change system settings
* Delete or archive protected records
* Access sensitive owner-only financial information

When Staff creates an order, its initial approval status must be:

```text
Pending Approval
```

The order should then appear in the Manager and Owner approval queue.

---

# 3. Order Approval Workflow

Separate the operational order status from the approval status.

## Approval Status

```text
Pending Approval
Approved
Rejected
```

## Operational Status

```text
Pending
Confirmed
Preparing
Packed
Shipped
Completed
Cancelled
```

Approval rules:

* Orders created by Staff begin as Pending Approval.
* Only the Manager or Owner can approve or reject an order.
* Staff cannot approve their own order.
* Rejected orders require a reason.
* Approval and rejection actions must create activity logs.
* The system must record `approved_by` and `approved_at`.
* An order cannot proceed to protected fulfillment stages until approved.
* The Owner may override an approval decision, but an override reason is required.

Suggested flow:

```text
Staff Creates Order
        ↓
Pending Approval
        ↓
Manager or Owner Reviews
        ↓
Approved or Rejected
        ↓
Confirmed
        ↓
Preparing
        ↓
Packed
        ↓
Shipped or Meet-up
        ↓
Completed
```

For meet-up orders, shipping information is not required.

---

# 4. Permission-Aware Interface

The interface must adapt to the logged-in role.

Examples:

## Staff Order Screen

Show:

* Create Order
* Save for Approval
* View Status
* View Timeline

Do not show:

* Approve
* Reject
* Owner Override
* Delete
* Financial Correction

## Manager Order Screen

Show:

* Review
* Approve
* Reject
* Update Fulfillment
* Manage Payment
* Cancel when authorized

## Owner Order Screen

Show all available actions, including protected administrative controls.

Unauthorized actions should not appear in menus, dropdowns, cards, quick actions, or keyboard shortcuts.

---

# 5. Activity Logging

Every important role-based action must create an activity log.

Examples:

* Staff created Order ORD-000124.
* Manager approved Order ORD-000124.
* Owner rejected Order ORD-000125.
* Manager recorded an expense.
* Staff viewed the Finance module.
* Manager adjusted inventory.
* Owner changed a user’s role.

Each log should include:

* User
* Role
* Action
* Module
* Record reference
* Previous value
* New value
* Date and time
* Reason or remarks when required

Activity logs are read-only and cannot be deleted.

---

# 6. Design Requirements

Maintain the existing design direction:

* White primary background
* Light-gray secondary background
* Soft shadows
* Rounded cards
* Clear typography
* Spacious layouts
* Minimal use of color
* Modern business application appearance
* Simple navigation
* Accessible contrast
* Consistent components

The design must be usable by employees with limited technical knowledge.

Every screen should make the next action clear.

Prioritize:

* Clear labels
* Fewer visible actions
* Role-specific quick actions
* Helpful empty states
* Confirmation dialogs
* Friendly validation messages
* Visible success feedback
* Simple mobile navigation

---

# 7. Required Design Outputs

Create responsive designs for:

* Owner dashboard
* Manager dashboard
* Staff dashboard
* Mobile orders list
* Mobile order details
* Mobile create-order form
* Order approval queue
* Mobile products list
* Mobile inventory list
* Mobile finance list
* Activity logs
* Role-aware sidebar and navigation
* Permission-denied state
* Loading states
* Empty states
* Error states
* Confirmation dialogs

Provide desktop, tablet, and mobile layouts.

The final result should feel like one consistent product, not separate desktop and mobile applications.

The system should be simple enough for Staff to create orders confidently, powerful enough for Managers to control operations and money, and complete enough for the Owner to manage the entire business.
