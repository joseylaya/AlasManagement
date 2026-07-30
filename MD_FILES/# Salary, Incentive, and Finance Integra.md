# Salary, Incentive, and Finance Integration

ALAS Business Manager must centralize employee compensation inside the Finance module.

The following users may have a configured salary:

* Owner
* Manager
* Staff

Salary and incentives must be recorded separately but displayed together as part of the user’s total compensation.

Compensation types:

```text
Salary
Activity Incentive
Quota Incentive
Bonus
Adjustment
```

---

## Core Financial Rule

A salary or incentive must not reduce business cash until the money is actually released.

The system must distinguish between:

```text
Amount Earned
Amount Approved
Amount Payable
Amount Paid
```

When a salary or incentive is approved but unpaid, it becomes a financial commitment.

When it is released, it becomes an actual cash-out transaction.

---

# Salary Configuration

Every Owner, Manager, and Staff account may have a salary profile.

A salary profile should contain:

```text
User
Salary amount
Salary frequency
Effective date
Payment method
Status
Created by
Updated by
```

Supported salary frequencies:

```text
Weekly
Bi-weekly
Semi-monthly
Monthly
Custom
```

Salary amounts must be configurable and must never be hardcoded.

---

# Salary Release Workflow

```text
Salary Period Created
        ↓
Salary Amount Calculated
        ↓
Pending Review
        ↓
Owner Approves Release
        ↓
Salary Becomes Payable
        ↓
Payment Is Released
        ↓
Finance Record Created
        ↓
Cash Transaction Created
        ↓
Activity Log Created
```

Recommended salary statuses:

```text
draft
pending_approval
approved
payable
paid
cancelled
```

Only the Owner, or an explicitly authorized Manager, may mark a salary as paid.

---

# Salary Release Financial Behavior

Before payment:

```text
Approved Salary
=
Committed Business Funds
```

Approved but unpaid salaries should reduce:

```text
Available Business Funds
```

They should not reduce:

```text
Recorded Business Cash
```

until the payment is actually released.

After payment:

```text
Cash Transaction Type:
salary

Direction:
cash_out
```

Example:

```text
Employee:
Maria Santos

Salary Period:
July 16–31, 2026

Salary:
₱6,000

Status:
Paid

Finance Entry:
Salary Expense

Cash Transaction:
- ₱6,000
```

---

# Owner Salary

The Owner may also have a configured salary.

Owner Salary and Owner Withdrawal must remain separate.

```text
Owner Salary
=
Planned compensation for work performed

Owner Withdrawal
=
Additional personal money taken from the business
```

Both reduce business cash when paid, but they must appear separately in Finance and reports.

The system must never automatically classify an Owner Withdrawal as Salary.

---

# Activity Incentive Financial Workflow

When an approved promotional activity qualifies for the daily activity incentive:

```text
Promotion Approved
        ↓
Activity Incentive Earned
        ↓
Incentive Added to Payable Compensation
        ↓
Owner Reviews
        ↓
Owner Releases Payment
        ↓
Finance and Cash Transaction Created
```

Example:

```text
Activity Incentive:
₱10

Date Earned:
July 30, 2026

Status:
Approved

Payment Status:
Unpaid
```

The ₱10 should not reduce business cash until it is actually paid.

---

# Quota Incentive Financial Workflow

When a Staff or Manager reaches the configured quota:

```text
Quota Reached
        ↓
System Creates Qualified Incentive
        ↓
Owner Reviews Qualification
        ↓
Owner Confirms Incentive Amount
        ↓
Incentive Becomes Payable
        ↓
Owner Posts It to Finance
        ↓
Payment Is Released
        ↓
Cash Transaction Created
```

The quota target and quota reward must be dynamic.

Example:

```text
Quota Period:
August 2026

Target:
15 qualified buyers

Progress:
15 / 15

Quota Reward:
₱500

Status:
Qualified
```

After qualification, the Owner should see:

```text
[Review Incentive]
[Post to Finance]
```

---

# Post to Finance Action

The **Post to Finance** action should centralize the qualified incentive inside the Finance module.

The action should create or update:

```text
Staff Incentive Record
Finance Payable Record
Financial Commitment
Activity Log
```

Posting to Finance does not necessarily mean the incentive has already been paid.

Recommended statuses:

```text
qualified
approved
posted_to_finance
paid
cancelled
```

When posted to Finance but still unpaid:

```text
Available Business Funds decreases
Recorded Business Cash remains unchanged
```

When marked as paid:

```text
Recorded Business Cash decreases
Cash Transaction is created
```

---

# Quota Incentive Payment

When the Owner releases the quota incentive, create:

```text
Finance Category:
Staff Incentive

Transaction Type:
quota_incentive

Direction:
cash_out
```

Example:

```text
Employee:
Maria Santos

Quota:
15 qualified buyers

Quota Period:
August 2026

Amount:
₱500

Cash Transaction:
- ₱500
```

The payment must link back to the quota incentive record.

---

# Prevent Duplicate Finance Records

A salary or incentive must only be posted to Finance once.

Use a unique reference between:

```text
Salary Release
Activity Incentive
Quota Incentive
Cash Transaction
```

Repeated button taps, retries, or offline synchronization must not create duplicate payments.

Buttons should be disabled while processing.

Example:

```text
Posting to Finance...
```

---

# Compensation Summary

Each user should have a compensation summary containing:

```text
Base Salary
Activity Incentives
Quota Incentives
Bonuses
Adjustments
Total Earned
Total Paid
Total Unpaid
Next Salary Release
```

Example:

```text
July 2026 Compensation

Salary:
₱6,000

Activity Incentives:
₱180

Quota Incentive:
₱500

Total Earned:
₱6,680

Paid:
₱6,180

Pending:
₱500
```

---

# Staff Mobile Dashboard Addition

The Staff incentive banner should also show a compact compensation summary.

Recommended display:

```text
Daily Activity

Earn ₱10 today
Status: Approved

Monthly Quota

15 / 15 qualified buyers
Quota achieved: ₱500

Compensation

Next salary release:
August 15, 2026

Pending incentive:
₱500

Paid this month:
₱6,180
```

The incentive and quota sections must remain the first content shown on the Staff mobile dashboard.

Salary information should appear directly below the incentive progress.

---

# Manager Mobile Dashboard Addition

The Manager dashboard should display:

```text
My Salary
My Incentives
Team Incentives Pending Review
Qualified Quota Incentives
Compensation Waiting for Owner Approval
```

Managers may review Staff promotion activity and quota progress.

Managers cannot approve or pay their own salary or incentive.

---

# Owner Dashboard Addition

The Owner dashboard should display:

```text
Upcoming Salary Releases
Total Salary Commitments
Activity Incentives Payable
Quota Incentives Qualified
Quota Incentives Posted to Finance
Total Compensation Due
Total Compensation Paid
```

The Owner should have quick actions:

```text
Review Salaries
Review Incentives
Post Incentive to Finance
Release Compensation
View Compensation History
```

---

# Role Rules

## Staff

Staff can:

* View their own salary information
* View their own activity incentives
* View their own quota incentives
* View paid and unpaid compensation
* View the next expected salary release

Staff cannot:

* Change their salary
* Change incentive amounts
* Post incentives to Finance
* Mark compensation as paid
* View another employee’s compensation

---

## Manager

Manager can:

* View their own salary and incentives
* View Staff incentive progress
* Review Staff promotion submissions
* Review qualified quota incentives
* Prepare compensation for Owner review

Manager cannot:

* Approve their own compensation
* Pay their own salary
* Pay their own incentive
* Change protected salary configurations
* View Owner compensation unless permitted

---

## Owner

Owner can:

* Configure salary profiles
* Configure salary schedules
* Configure activity incentives
* Configure quota incentives
* Approve salary releases
* Approve incentives
* Post qualified incentives to Finance
* Release compensation
* Mark compensation as paid
* View all salary and incentive history
* Correct records through adjustment transactions

---

# Activity Logging

The following actions must create Activity Logs:

```text
Salary Profile Created
Salary Profile Updated
Salary Release Generated
Salary Approved
Salary Paid
Activity Incentive Earned
Quota Incentive Qualified
Incentive Approved
Incentive Posted to Finance
Incentive Paid
Compensation Adjusted
```

Each log should include:

```text
User
Employee
Compensation type
Amount
Period
Previous status
New status
Performed by
Date and time
Remarks
```

---

# Finance Dashboard Impact

The Finance dashboard should include:

```text
Recorded Business Cash
Available Business Funds
Upcoming Salaries
Approved Unpaid Salaries
Approved Unpaid Incentives
Compensation Paid This Month
Owner Salary
Owner Withdrawals
```

Formula:

```text
Available Business Funds
=
Recorded Business Cash
-
Approved Unpaid Salaries
-
Approved Unpaid Incentives
-
Other Committed Expenses
```

This ensures the Owner does not spend money that is already intended for employee compensation.

---

# Offline Behavior

Salary and incentive information may be viewed from the locally cached dashboard.

However, the following actions should require an internet connection:

```text
Approve Salary
Post Incentive to Finance
Mark Salary as Paid
Mark Incentive as Paid
Modify Salary Configuration
```

This prevents duplicate payments and inconsistent financial records.

When offline, display:

```text
Compensation information may be outdated.

Connect to the internet before approving or releasing payment.
```

---

# Additional Business Rules

1. Every active user may have only one active salary profile at a time.
2. Salary changes must have an effective date.
3. Historical salary releases must not change when the salary profile changes.
4. Activity incentives are earned only after promotional proof is approved.
5. Quota incentives are qualified only after the required valid orders are completed.
6. Approved unpaid salary and incentive amounts are financial commitments.
7. Financial commitments reduce Available Business Funds.
8. Business cash decreases only when payment is actually released.
9. Every salary and incentive payment creates a Cash Transaction.
10. Owner Salary and Owner Withdrawal must remain separate.
11. Managers and Staff cannot approve their own compensation.
12. Paid compensation records cannot be deleted.
13. Corrections require adjustment or reversal records.
14. Duplicate salary and incentive payments must be prevented.
15. All amounts, quota targets, periods, and approval requirements must be configurable.

---

# Final Financial Objective

ALAS Business Manager must centralize all compensation-related money.

The Owner should always be able to answer:

* How much salary is due?
* How much incentive has been earned?
* How much compensation is still unpaid?
* How much has already been released?
* How much money is already committed?
* How much business cash is still safe to use?

Salary and incentive tracking must strengthen financial discipline rather than create another separate source of records.
