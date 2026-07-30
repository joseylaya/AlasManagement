# ALAS Business Manager — Offline Sales, Cash Tracking, and Mobile UI Fix

Improve the existing Laravel + Livewire ALAS Business Manager application.

The highest-priority business problem is cash handling.

Sales money is sometimes mixed with the Owner’s personal money and spent without being properly recorded. The application must make every cash movement visible and traceable, even when the device temporarily has no internet connection.

Do not redesign the entire application. Preserve the approved Stitch AI design, white theme, soft shadows, rounded cards, typography, navigation, and existing desktop interface.

Focus on:

1. Offline sales entry
2. Offline owner cash withdrawal entry
3. Automatic synchronization
4. Accurate cash transaction tracking
5. Mobile button and interaction fixes
6. Clear synchronization status
7. Prevention of duplicate records

---

# 1. Core Financial Principle

Every peso entering or leaving ALAS Clothing must be recorded.

The system must clearly separate:

* Business cash
* Business expenses
* Owner withdrawals
* Personal money

Owner withdrawals are not business expenses.

An Owner Withdrawal reduces business cash but does not reduce operating profit.

The application must help the Owner understand:

* How much cash came from sales
* How much cash was withdrawn personally
* How much was spent on business expenses
* What the current recorded business cash should be
* Which transactions have not yet synchronized
* Who created or updated each transaction

---

# 2. Offline-First Web Application

Make the application function as an offline-capable responsive web application.

Use Progressive Web App principles where appropriate.

The user must be able to perform supported operations even when:

* Wi-Fi is disconnected
* Mobile data is unavailable
* The connection is unstable
* The server temporarily cannot be reached

Supported offline operations for Version 1:

* Create a sale
* Update an unsynchronized sale
* Create an Owner Withdrawal
* Update an unsynchronized Owner Withdrawal
* View locally cached products
* View locally cached inventory availability
* View pending synchronization records

Operations requiring live server verification may remain unavailable offline.

---

# 3. Local Offline Storage

Use browser-side persistent storage suitable for structured offline data.

Preferred:

```text
IndexedDB
```

Do not rely only on:

```text
localStorage
```

because localStorage is not suitable for reliable structured transaction storage.

Store offline records locally until synchronization succeeds.

Each offline record must include:

```text
local_uuid
record_type
action_type
payload
local_status
created_at_local
updated_at_local
created_by
sync_attempts
last_sync_attempt_at
sync_error
server_id
```

Recommended local statuses:

```text
pending_sync
syncing
synced
sync_failed
conflict
```

Use a client-generated UUID to prevent duplicate records during repeated synchronization attempts.

---

# 4. Automatic Synchronization

When the browser detects that an internet connection is available:

1. Check for pending offline records.
2. Synchronize them in creation order.
3. Send each record with its unique local UUID.
4. Wait for a confirmed server response.
5. Store the returned server record ID.
6. Mark the local record as synced.
7. Refresh the displayed server data.
8. Show a clear synchronization result.

Synchronization should also run:

* When the application opens
* When the user logs in
* When the browser returns online
* When the user taps “Sync Now”
* At reasonable intervals while the application is open

Do not delete the local record until the server confirms successful synchronization.

---

# 5. Duplicate Prevention

Synchronization must be idempotent.

The same offline transaction must never create multiple server records.

The server must store and validate:

```text
client_uuid
```

For example:

```text
client_uuid CHAR(36) UNIQUE
```

When the same `client_uuid` is submitted again, return the existing server record instead of creating another record.

This rule applies to:

* Sales
* Owner Withdrawals
* Cash Transactions
* Offline activity records

---

# 6. Sale Creation While Offline

The user must be able to create a sale while offline.

Required sale fields:

```text
Customer name or reference
Order items
Product
Quantity
Unit price snapshot
Discount
Total
Payment method
Payment status
Fulfillment method
Order status
Created by
Local creation date and time
```

Supported fulfillment methods:

```text
Shipping
Meet-up
```

Shipping details are only required when Shipping is selected.

Meet-up details may include:

```text
Meet-up location
Meet-up date
Meet-up time
Remarks
```

When a sale is created offline:

1. Generate a local UUID.
2. Save the order locally.
3. Save the order items locally.
4. Save a pending cash transaction locally when the payment has been received.
5. Show the order immediately in the interface.
6. Display an “Offline — Pending Sync” badge.
7. Synchronize automatically when the connection returns.

Example badge:

```text
Pending Sync
```

Do not display an offline sale as fully synchronized until the server confirms it.

---

# 7. Updating Sales While Offline

Allow users to update sales while offline, subject to permissions.

Offline updates may include:

* Customer information
* Order items
* Quantity
* Payment information
* Fulfillment information
* Notes
* Order status when permitted

Do not allow Staff to approve orders offline or online.

Orders created by Staff must begin with:

```text
Pending Approval
```

Only Manager and Owner may approve or reject orders.

Do not allow an offline update to silently overwrite a newer server version.

Use a version or timestamp field such as:

```text
server_updated_at
record_version
```

During synchronization:

* If the server record has not changed, apply the offline update.
* If the server record changed after the offline copy was cached, mark it as a conflict.
* Do not silently overwrite server data.
* Show the conflict to an authorized user for review.

---

# 8. Owner Cash Withdrawal While Offline

Only the Owner may create or update Owner Withdrawals.

Required fields:

```text
Amount
Withdrawal date
Reason
Payment source
Remarks
Created by
Local creation date and time
```

Examples of reasons:

```text
Personal allowance
Emergency personal use
Household expense
Owner compensation
Other
```

When an Owner Withdrawal is created offline:

1. Generate a local UUID.
2. Save the withdrawal locally.
3. Create a linked pending cash transaction locally.
4. Show it immediately in the Owner’s cash history.
5. Mark it as “Pending Sync.”
6. Synchronize automatically when the internet returns.
7. Create an Activity Log after successful synchronization.

Owner Withdrawals must not be categorized as operating expenses.

They must be displayed separately in reports.

---

# 9. Cash Transaction Rules

Every confirmed financial action must create one cash transaction.

Cash transaction types:

```text
sale
expense
owner_withdrawal
salary
refund
adjustment
```

Transaction directions:

```text
cash_in
cash_out
```

Examples:

```text
Sale
Direction: cash_in
Amount: ₱1,500

Owner Withdrawal
Direction: cash_out
Amount: ₱500
```

Cash Transactions should include:

```text
id
client_uuid
transaction_number
transaction_type
direction
amount
transaction_date
source_type
source_id
description
created_by
updated_by
created_at
updated_at
sync_source
```

`sync_source` may contain:

```text
online
offline_sync
```

Cash transactions should not be silently edited after synchronization.

Corrections should use a reversal or adjustment transaction when appropriate.

---

# 10. Cash Dashboard

Update the Owner dashboard to clearly display:

```text
Recorded Business Cash
Today’s Cash In
Today’s Cash Out
Today’s Sales
Today’s Owner Withdrawals
Today’s Expenses
Pending Offline Transactions
Failed Synchronizations
Last Successful Sync
```

Do not make the bank balance the main decision metric.

The dashboard should prioritize recorded business cash and traceable cash movements.

Add a visible warning when pending offline transactions may affect the displayed balance.

Example:

```text
Your current cash total excludes 3 transactions waiting to synchronize.
```

The Owner should be able to tap the warning and view those transactions.

---

# 11. Cash Timeline

Create a simple business cash timeline.

Example:

```text
+ ₱1,500
Sale ORD-000124
Recorded by Maria
Synced

- ₱500
Owner Withdrawal
Recorded by Jose
Pending Sync

- ₱250
Packaging Expense
Recorded by Manager
Synced
```

Each transaction should show:

* Type
* Direction
* Amount
* Description
* User
* Date and time
* Sync status
* Reference record
* Payment method

Use clear positive and negative indicators, but do not rely only on color.

---

# 12. Sync Status Interface

Add a synchronization indicator to the application header.

Possible states:

```text
Online
Offline
Syncing
All Data Synced
3 Pending
Sync Failed
```

When offline, show a persistent but non-blocking banner:

```text
You are offline. New sales and Owner Withdrawals will be saved on this device and synchronized automatically when a connection becomes available.
```

Provide a “Sync Now” action when online.

Create a dedicated Sync Queue screen containing:

* Record type
* Local UUID
* Action
* Created date
* Created by
* Sync status
* Attempt count
* Last error
* Retry action

Only Owner and Manager may view detailed synchronization errors.

Staff may only see whether their records are pending or synchronized.

---

# 13. Mobile UI and Button Fixes

The application currently works correctly on desktop but some buttons do not work properly on mobile.

Audit and fix all mobile interactions.

Check:

* Buttons
* Dropdown menus
* Modal triggers
* Form submissions
* Row actions
* Tabs
* Date pickers
* Select inputs
* Search
* Filters
* Pagination
* Sidebar drawer
* Notification dropdown
* Confirmation dialogs
* Sticky action buttons
* File uploads

Do not use hover as the only way to reveal an action.

Mobile devices do not have hover behavior.

Ensure clickable elements are not blocked by:

* Invisible overlays
* Incorrect `z-index`
* Disabled pointer events
* Parent elements intercepting taps
* Fixed headers
* Off-screen dropdowns
* Nested clickable elements
* Incorrect Alpine.js event handlers

Verify that buttons use the correct type:

```html
type="button"
```

for non-submit actions, and:

```html
type="submit"
```

only for actual form submission.

Use Livewire loading states to prevent repeated tapping.

Example:

```text
Disable button while request is processing.
Show “Saving...” or a loading indicator.
```

---

# 14. Mobile List Design

Do not display large desktop tables unchanged on mobile.

For screens below 768px:

* Convert tables into cards or compact list rows.
* Keep the primary action visible.
* Place secondary actions inside an overflow menu.
* Avoid horizontal scrolling whenever possible.
* Show only the most important fields.
* Allow details to be expanded or opened on a separate page.

For sales cards, show:

```text
Order number
Customer
Total
Payment status
Approval status
Fulfillment method
Sync status
Primary action
```

For cash transaction cards, show:

```text
Type
Amount
Direction
Description
Date
Recorded by
Sync status
```

---

# 15. Role Permissions

## Owner

The Owner can:

* Create and update sales
* Approve and reject orders
* Manage all order statuses
* Create and update Owner Withdrawals
* View all finances
* View all cash transactions
* Retry failed synchronization records
* Resolve synchronization conflicts
* View all logs
* Manage users
* Manage settings
* Perform all supported actions

## Manager

The Manager can:

* Create and update sales
* Approve and reject Staff orders
* Manage operational order statuses
* View and manage permitted finances
* Record expenses
* View cash transactions
* Retry failed operational sync records
* View activity logs
* Manage inventory
* View synchronization status

The Manager cannot:

* Create Owner Withdrawals
* Modify Owner Withdrawals
* Resolve protected Owner-only financial conflicts
* Change Owner permissions
* Change protected system settings

## Staff

The Staff can:

* Create sales
* Update permitted unsynchronized sales
* View their submitted orders
* View order status
* View permitted logs
* Open Finance in read-only mode
* View permitted cash information
* View synchronization status for their own records

The Staff cannot:

* Approve orders
* Reject orders
* Create Owner Withdrawals
* Modify cash transactions
* Resolve conflicts
* Adjust inventory
* Manage users
* Modify settings

---

# 16. Activity Logging

Create an activity log for every successful synchronized action.

Examples:

```text
Staff created Order ORD-000124 offline.
Order ORD-000124 synchronized successfully.
Manager updated Order ORD-000124.
Owner recorded a ₱500 Owner Withdrawal offline.
Owner Withdrawal synchronized successfully.
Synchronization failed for Order local UUID abc-123.
```

Activity Logs should include:

```text
User
Role
Action
Module
Reference
Client UUID
Server ID
Timestamp
Previous values
New values
Device status
Sync source
Error details when applicable
```

Do not store sensitive authentication information in logs.

---

# 17. Offline Security

Offline data may contain sensitive business information.

Implement:

* Secure authenticated access
* Session protection
* Local data isolation by user
* Logout cleanup rules
* No storage of plain-text passwords or tokens
* Automatic invalidation of local access when the user account is disabled
* Limited offline cache for sensitive finance screens
* Owner-only local access to Owner Withdrawal data

Do not cache more sensitive data than required.

On logout:

* Prevent the next user from seeing the previous user’s unsynchronized private records.
* Preserve pending data securely only when safe and associated with the correct authenticated account.
* Warn the user before logout when unsynchronized records exist.

Example:

```text
You have 2 records waiting to synchronize. Logging out now may delay synchronization.
```

---

# 18. Error Handling

Use clear messages.

Examples:

```text
Your sale was saved on this device and will synchronize when you are back online.
```

```text
The transaction could not be synchronized because the server record changed. Please review the conflict.
```

```text
Synchronization failed. Your data remains safely stored on this device.
```

Never display raw SQL, PHP, JavaScript, or network errors to normal users.

---

# 19. Acceptance Criteria

The implementation is complete when:

* A sale can be created without internet access.
* An unsynchronized sale can be updated offline.
* The Owner can create a cash withdrawal offline.
* The Owner can update an unsynchronized withdrawal offline.
* Offline records synchronize automatically after reconnection.
* Duplicate server records are not created.
* Every synchronized sale creates the correct cash transaction when paid.
* Every Owner Withdrawal creates a cash-out transaction.
* Owner Withdrawals are excluded from business expenses.
* Sync failures remain visible and retryable.
* Conflicts are not silently overwritten.
* Mobile buttons work correctly.
* Desktop functionality remains unchanged.
* Mobile tables are replaced by usable cards or compact lists.
* Role permissions are enforced in both the interface and backend.
* Every important action is traceable.

---

# Final Objective

The system must protect ALAS Clothing from losing visibility over business cash.

The Owner should always be able to answer:

* How much money entered the business?
* How much money left?
* How much was withdrawn personally?
* Which transactions are waiting to synchronize?
* Who recorded each transaction?
* Is the displayed cash total complete and trustworthy?

The application should remain usable during connection problems and synchronize safely when connectivity returns.

The final experience must be reliable, simple, mobile-friendly, and understandable by non-technical employees.
