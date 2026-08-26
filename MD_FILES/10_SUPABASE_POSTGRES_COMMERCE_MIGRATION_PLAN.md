# ALAS Supabase PostgreSQL and Commerce Integration Plan

**Status:** Production PostgreSQL cutover complete; payment and remaining e-commerce work pending
**Last updated:** 2026-08-24
**Source of truth:** This file governs the database migration and ALAS E-commerce integration. Update it whenever a decision, schema, phase status, risk, or cutover result changes.

## 1. Objective

Move ALAS Management from its production SQLite database to Supabase-hosted PostgreSQL without losing operational, user, finance, activity, incentive, point, or monetary history. After migration, ALAS Management (Laravel) will be the single commerce backend and business-rules authority for both the admin application and ALAS E-commerce.

```text
ALAS Admin (Laravel UI) ─┐
                        ├─> ALAS Management Laravel domain/services
ALAS E-commerce ─REST───┘              │
                                       ├─> Supabase PostgreSQL
                                       ├─> Supabase Storage (product/proof media)
                                       └─> PayMongo API and verified webhooks
```

Supabase PostgreSQL is the system of record. Laravel connects to it with the PostgreSQL driver. The storefront does not directly mutate commerce tables through Supabase PostgREST.

## 2. Non-negotiable decisions

1. There is one authoritative commerce database: Supabase PostgreSQL.
2. There is one authoritative business-logic backend: ALAS Management Laravel.
3. ALAS E-commerce may use Next.js server-side features, but it does not own a separate database or duplicate inventory, order, discount, or payment logic.
4. Public storefront writes go through versioned Laravel REST endpoints.
5. Laravel recalculates prices, discounts, shipping, totals, and stock from trusted database values.
6. PayMongo status changes are accepted only through authenticated server operations and verified, idempotent webhooks.
7. Existing SQLite IDs, relationships, password hashes, timestamps, soft-deleted rows, audit history, activity proofs, points, and monetary values must be preserved or explicitly mapped and reconciled.
8. SQLite remains unchanged and recoverable until PostgreSQL passes reconciliation and the rollback window closes.
9. Monetary columns use fixed precision (`numeric(15,2)` or integer centavos where the commerce schema deliberately requires it), never floating point.
10. Database credentials and Supabase service-role credentials must never be exposed to the browser or committed to Git.

## 3. Systems and ownership

| Concern | Owner | Rule |
|---|---|---|
| Admin UI and staff authentication | Laravel | Existing roles and permissions remain authoritative. |
| Product/SKU management | Laravel | Storefront products group sellable SKUs/variants. |
| Inventory | Laravel services + PostgreSQL | Mutate inside transactions with real row-level locks. |
| Orders and fulfillment | Laravel | Storefront and admin orders share the same tables and state machine. |
| Checkout API | Laravel | Idempotent order creation and fresh inventory validation. |
| Payments | Laravel + PayMongo | Store provider IDs/events; verify webhook signatures and deduplicate events. |
| Storefront rendering/cart UX | Next.js | Cart is provisional until Laravel confirms checkout. |
| Product and proof files | Supabase Storage or approved object storage | Database stores durable object keys, not local-container-only paths. |
| Database | Supabase PostgreSQL | Direct access is server-only except narrowly approved read policies. |

## 4. Protected SQLite recovery scope

Migration includes every application table, not only commerce tables. The following data is specifically protected because it represents identity, money, earned value, or audit evidence.

### Users and access history

- `users`, including stable IDs, username/email, password hashes, role, status, creator/updater, timestamps, and soft deletion
- `sessions` are not migrated; all users sign in again after cutover
- `activity_logs`, preserving nullable actor references, subject references, properties, IP, and timestamps
- notifications, announcements, push-related records, settings, and other user-linked application records where still valid

### Finance and money

- `financial_accounts`
- capital contribution/allocation tables present in the final source schema
- `cash_transactions`
- `expense_categories` and `expenses`
- `owner_drawals`
- `salary_profiles`
- `compensation_records`
- order-derived sales/refunds and all foreign-key links between finance records

For every money table, reconciliation must compare row count and exact decimal totals grouped by type, direction, status, financial account, and user where applicable. A matching grand total alone is insufficient.

### Applied activities, incentives, points, and payouts

- `promotion_activities`, including review state, approved amount, reviewer, evidence metadata, and timestamps
- proof files referenced by `proof_path`; files must be copied to durable storage and checksummed before paths are changed
- `performance_point_entries`, preserving `source_type`, `source_id`, event, signed points, and award time
- `compensation_records.promotion_activity_id`, maintaining one activity-to-compensation relationship
- all activity-incentive, quota-incentive, salary, bonus, and adjustment records regardless of payment status
- `cash_transaction_id` links for paid compensation so an earned incentive cannot be paid twice

Do not recalculate historical points or incentive money during migration. Import the immutable ledger entries and their existing relationships. Recalculation may be run only as a comparison report.

## 5. Current local baseline (informational, not a cutover manifest)

Observed on 2026-08-24 in `AlasProj/database/database.sqlite`:

| Entity | Rows | Control value |
|---|---:|---:|
| Users | 4 | n/a |
| Activity logs | 19 | n/a |
| Promotion activities | 4 | ₱10 approved amount |
| Performance-point entries | 4 | 4 signed points |
| Compensation records | 1 | ₱10 amount |
| Cash transactions | 1 | ₱5,000 stored amount |
| Expenses | 0 | ₱0 |
| Owner drawals | 0 | ₱0 |
| Salary profiles | 0 | n/a |

The archived `alas db prod/alas-production.sqlite` had the older tables but zero application rows when inspected. It must not be mistaken for the live source. Before migration, identify the authoritative server database and record its path, file hash, size, SQLite integrity result, modification time, and backup location in the cutover log.

## 6. Target data model work

### Preserve and PostgreSQL-harden the management schema

- Run all Laravel migrations against a clean PostgreSQL staging database.
- Audit SQLite-oriented migrations, raw SQL, enum/check constraints, booleans, JSON/text fields, date handling, and auto-increment assumptions.
- Add missing foreign keys and deliberate delete behavior only after legacy rows pass orphan checks.
- Convert IDs consistently. Preserve current integer IDs for management entities unless a documented API boundary needs an additional opaque public UUID.
- Reset every PostgreSQL identity sequence to `max(id) + 1` after import.
- Add indexes for foreign keys, status/date filters, SKU/slug, order lookup, webhook provider IDs, idempotency keys, and ledger reconciliation queries.

### Consolidate commerce schema

- Keep a storefront product entity for customer-facing content and grouping.
- Keep management `products` as inventory-owning SKUs/variants, or rename only through a separately approved compatibility migration.
- Link each sellable SKU to a storefront product; do not create a second inventory balance.
- Add product images/media with ordering and accessible metadata.
- Extend the existing orders/order-items model for public checkout while preserving historical order snapshots.
- Add shipping, discounts, payment attempts/events, webhook events, and fulfillment data as normalized tables.
- Archive or replace the independent migration under `alas-ecom/supabase/migrations` after the Laravel schema becomes authoritative; never deploy two competing definitions.

## 7. REST API contract

Initial versioned endpoints:

```text
GET  /api/v1/storefront/products
GET  /api/v1/storefront/products/{slug}
POST /api/v1/storefront/checkouts
GET  /api/v1/storefront/orders/{public_token}
POST /api/v1/payments/paymongo/checkout
POST /api/v1/webhooks/paymongo
```

Requirements:

- Version responses and document them with OpenAPI.
- Use opaque public order tokens; never expose sequential IDs as authorization.
- Require idempotency keys for checkout and payment creation.
- Rate-limit public writes and sensitive reads.
- Return stable machine error codes for out-of-stock, price changes, invalid delivery data, and payment state.
- Authenticate server-to-server storefront calls where necessary; keep secrets on the Next.js server.
- Cache public catalog reads, but always read fresh price and inventory inside checkout transactions.
- Apply CORS narrowly to approved storefront origins.

## 8. Migration execution plan

### Phase 0 — Safety and discovery

- [x] Identify the authoritative production SQLite file and create a consistent rehearsal snapshot. Final cutover freeze remains pending.
- [ ] Put the application into a short maintenance/read-only window for the final export.
- [ ] Create at least two byte-for-byte final-cutover backups in separate locations. One hashed rehearsal backup currently exists on the server and one temporary local copy was verified.
- [ ] Record SHA-256 hashes and run `PRAGMA integrity_check` and `PRAGMA foreign_key_check`.
- [x] Capture rehearsal schema, migration list, table counts, maximum IDs, null/orphan checks, and grouped monetary/point totals.
- [ ] Inventory local uploaded files, especially promotion proof files and product images.
- [ ] Produce a redacted migration manifest without password hashes, tokens, or personal data in Git.

Implemented support: `php artisan alas:audit-sqlite /absolute/source.sqlite --output=/secure/path/audit.json` performs read-only integrity/foreign-key checks and emits the hashed, redacted counts and finance/activity/point controls required for the manifest. The active local SQLite passed this audit on 2026-08-24; production source identification remains open.

**Exit gate:** authoritative source and restore procedure are proven.

### Phase 1 — Supabase environment

- [ ] Create separate development, staging, and production Supabase projects or isolated approved environments.
- [x] Verify Laravel PostgreSQL connectivity using the Supabase Session Pooler with SSL.
- [ ] Store secrets only in environment/secret management.
- [ ] Configure network policy, backups/PITR as available, monitoring, and least-privilege database roles.
- [ ] Decide Supabase Auth usage separately. Initial migration keeps Laravel authentication and password hashes.
- [ ] Create private storage for activity proof files and suitable product-media buckets.

**Exit gate:** Laravel can migrate, transact, queue, and run tests against staging PostgreSQL.

### Phase 2 — Schema compatibility

- [x] Run the full Laravel migration chain on empty Supabase PostgreSQL.
- [ ] Resolve PostgreSQL compatibility issues with forward migrations; do not rewrite already-applied history casually.
- [x] Add initial consolidated storefront product/variant relationships and checkout constraints.
- [ ] Add payment idempotency and webhook-event uniqueness constraints. Checkout idempotency is complete.
- [ ] Verify every `lockForUpdate()` path for inventory, order cancellation, payment, and compensation payout. Storefront checkout locking is implemented and SQLite-tested; PostgreSQL concurrency testing remains open.
- [x] Restore the missing `performance_point_entries` migration so clean PostgreSQL builds retain earned-point schema.
- [ ] Make the PostgreSQL schema dump/rebuild reproducible in CI.

**Exit gate:** clean rebuild and automated test suite pass on PostgreSQL.

### Phase 3 — Repeatable SQLite importer

- [x] Build a transactional command that reads a specified SQLite snapshot and writes to PostgreSQL.
- [x] Require a clean target and refuse silent duplication.
- [x] Preserve primary keys and copy only columns present in the migrated target schema.
- [ ] Import users before all user-linked records.
- [ ] Import products/orders before their items, stock, and finance references.
- [ ] Import activities before point entries and compensation; import cash transactions in a dependency-safe pass.
- [ ] Handle circular/nullable finance references with staged inserts followed by verified link updates.
- [ ] Copy proof/media objects, record checksums, then update paths only after successful upload.
- [x] Reset sequences and run row-count/referential-integrity checks before commit.
- [x] Generate a machine-readable SQLite audit/control report.

Implemented command: `php artisan alas:import-sqlite-to-postgres /absolute/frozen.sqlite --write`. It refuses a non-PostgreSQL destination, a damaged source, or any non-empty destination table, and rolls the transaction back on count/orphan failures. A real Supabase staging rehearsal and grouped PostgreSQL monetary comparison remain required before this phase can pass its exit gate.

**Exit gate:** importer can reproduce identical staging results from the same snapshot.

**Rehearsal result (2026-08-24):** Passed against Supabase PostgreSQL using live snapshot SHA-256 `62cea04791b19e582ac63e75d5a129dbd1f5941fc8c895f26cd5eeacaa38c154`. All 27 applicable tables imported transactionally. Count and orphan gates passed. Importer was hardened for exact migration-seeded financial accounts and keyless Laravel tables after two safely rolled-back attempts.

### Phase 4 — Reconciliation and recovery proof

- [x] Compare every imported table's rehearsal row count and run primary-key sequence reset.
- [x] Compare rehearsal users and password-hash equality without exposing hashes.
- [x] Compare rehearsal activity status and activity-to-compensation relationships.
- [x] Compare rehearsal signed point ledger entries per user/source/event.
- [x] Compare rehearsal compensation entries and cash links.
- [x] Compare rehearsal cash controls and exact ledger rows.
- [x] Verify no orphaned user, activity, point, compensation, or finance references in rehearsal.
- [ ] Verify proof/media object count, size, checksum, access control, and application rendering.
- [ ] Test sign-in for one account per role and force logout of legacy sessions.

**Exit gate:** zero unexplained differences. Owner signs the reconciliation report.

### Phase 5 — Laravel commerce backend

- [ ] Point development Laravel at PostgreSQL and remove SQLite-only runtime assumptions. Environment configuration is ready; a Supabase development connection is still required.
- [ ] Implement and document the full v1 storefront API. Versioned catalog, checkout, and opaque tracking endpoints are implemented; OpenAPI and payments remain.
- [ ] Implement transactional checkout, order snapshots, stock reservation/deduction policy, and cancellation restoration. Transactional creation/deduction and snapshots are complete; unpaid-order expiry/release policy remains.
- [ ] Implement PayMongo payment creation and verified idempotent webhook processing.
- [ ] Ensure public checkout cannot select internal payment/approval states.
- [ ] Add API, concurrency, authorization, webhook replay, and finance regression tests.
- [ ] Load-test simultaneous checkout and admin inventory adjustment.

**Exit gate:** staging checkout-to-fulfillment and payment flows pass without overselling or duplicate money entries.

### Phase 6 — ALAS E-commerce integration

- [x] Replace production catalog and checkout data paths with the Laravel v1 API client. Local fallback remains development-only unless explicitly enabled.
- [x] Keep management API access in Next.js server code.
- [ ] Implement resilient loading, stock/price-change handling, and checkout idempotency. Server-proxied idempotent creation is complete; full correction/payment UX remains.
- [ ] Remove or archive duplicate Supabase schema ownership from the e-commerce repository.
- [ ] Test SEO/server rendering, caching, cart behavior, checkout, and order tracking.

**Exit gate:** e-commerce has no independent commerce database writes.

### Phase 7 — Production rehearsal and cutover

- [x] Perform at least one migration rehearsal from a recent production snapshot.
- [x] Record the rollback artifacts and preserve the prior Compose/Docker/code configuration.
- [x] Enter maintenance mode, stop queue/scheduler writers, take final hashed SQLite snapshots, and export control totals.
- [x] Run exact production migrations, importer, sequence reset, reconciliation, and smoke tests.
- [x] Switch Laravel production web, queue, and scheduler database configuration to Supabase PostgreSQL.
- [x] Restart web/workers and deploy the shared v1 commerce API.
- [ ] Continue monitoring database connections, errors, checkout/payment idempotency, inventory, and finance totals through the observation window.
- [x] Keep SQLite unchanged and backed up through the retention period.

**Exit gate:** production is stable, reconciliation remains clean, and rollback window is formally closed.

## 9. Rollback plan

Before cutover, rollback means continuing to use SQLite. After cutover, rollback is allowed only while PostgreSQL writes can be fully accounted for.

1. Stop storefront/admin writes and queue workers.
2. Capture a PostgreSQL incident snapshot and export all post-cutover writes.
3. If no authoritative new writes exist, switch Laravel back to the preserved final SQLite snapshot.
4. If new orders, payments, inventory movements, activities, points, or finance entries exist, do not blindly switch back. Reconcile and replay them through a reviewed reverse-migration procedure first.
5. Keep PayMongo webhooks retryable/idempotent during any outage; never acknowledge an event that was not durably recorded.

## 10. Testing and acceptance criteria

- All Laravel unit/feature/E2E tests pass using PostgreSQL.
- Concurrent orders cannot reduce inventory below zero.
- Replayed checkout requests, offline sync requests, payment requests, and webhooks do not duplicate orders, points, compensation, or cash transactions.
- Owner/manager/staff authorization remains unchanged or becomes stricter.
- Every protected SQLite row is imported or listed in an owner-approved exception report.
- All exact money and signed-point control totals match at cutover.
- Historical activity, proof, reviewer, incentive, payment, and audit relationships remain navigable.
- Password hashes work with Laravel authentication; users are not silently recreated with new IDs.
- Backups and documented restoration are tested, not merely configured.
- E-commerce performs no direct privileged Supabase mutation.

## 11. Risks and mitigations

| Risk | Mitigation |
|---|---|
| Wrong/empty SQLite selected | Hash and identify the authoritative live file; compare counts before maintenance. |
| Decimal differences | Explicit fixed-precision mapping and grouped control totals. |
| Lost IDs or broken relations | Preserve IDs, import in dependency order, reset sequences, run orphan checks. |
| Incentive paid twice | Preserve unique activity-compensation and compensation-cash links; test idempotency. |
| Activity proof inaccessible | Checksum and copy files before updating paths; verify private access. |
| Overselling | PostgreSQL transactions, row locks, deterministic lock order, fresh stock validation. |
| Duplicate schema ownership | Laravel migrations are authoritative; archive the e-commerce copy. |
| Supabase credentials exposed | Server-only environment secrets and narrowly scoped roles. |
| Connection exhaustion | Use the appropriate Supabase pooler and set bounded Laravel/worker concurrency. |
| Rollback loses new money/orders | Short decision window, write freeze, post-cutover ledger capture, reviewed replay. |

## 12. Progress log

| Date | Change | Evidence/decision |
|---|---|---|
| 2026-08-24 | Architecture selected | Laravel is the shared backend; Supabase PostgreSQL is the single database; e-commerce consumes Laravel REST. |
| 2026-08-24 | Local SQLite inventory recorded | Active local DB contains users, activity history, promotion activities, performance points, compensation, and cash data. Archived database file inspected as empty and excluded as presumed source. |
| 2026-08-24 | Plan created | Implementation remains pending; no database mutation or production migration performed. |
| 2026-08-24 | Migration safety tooling implemented | Added hashed SQLite audit/control reporting and a transactional, empty-target-only PostgreSQL importer with count/orphan checks and sequence reset. Active SQLite was read-only audited successfully. |
| 2026-08-24 | Earned-points schema recovered | Restored the missing `performance_point_entries` migration from the live SQLite schema so clean PostgreSQL deployments include points. |
| 2026-08-24 | Shared commerce API implemented | Added versioned catalog, idempotent public checkout, server-priced order snapshots, deterministic inventory locks, opaque tracking, rate limits, and CORS write support. Backend suite passes 106 assertions. |
| 2026-08-24 | Storefront connected | Next.js production catalog and checkout now call Laravel v1 server-to-server. Tests/typecheck pass and lint has no errors. Supabase credentials, PostgreSQL rehearsal, PayMongo, proof-media transfer, and production cutover remain pending. |
| 2026-08-24 | Supabase schema initialized | Verified PostgreSQL 17.6 through the Singapore Session Pooler, confirmed an empty public schema, and successfully ran all 32 Laravel migrations. Supabase now contains 34 application tables and zero imported users/point entries. Data import intentionally awaits confirmation of the authoritative SQLite source. |
| 2026-08-24 | Live rehearsal snapshot verified | Confirmed the active container mount and authoritative SQLite path, created an online-consistent server backup, downloaded it temporarily, matched SHA-256, and passed SQLite integrity/foreign-key audit. Snapshot contains 12 users, 113 logs, 10 activities, 10 point entries, eight payable incentives totaling ₱16, and ₱2,450 net stored cash-transaction amount. |
| 2026-08-24 | Supabase rehearsal import passed | Imported 27 applicable live tables into Supabase. All counts, sequences, and foreign-key orphan checks passed. Independent comparison found zero mismatches across 12 password hashes, cash transactions, eight compensation records, ten point entries, and ten promotion activities. This is a rehearsal snapshot; live ALAS remains on SQLite and continues accepting writes. |
| 2026-08-24 | Production cutover completed | Entered Laravel maintenance mode, stopped queue/scheduler writers, preserved two final SQLite backups and rollback configuration, rebuilt Supabase with the exact production migration set plus storefront checkout, and imported all 28 applicable live tables including recurring announcements. Final backup SHA-256: `172c9accf41280e592f71651de8f15762ddef993a71330b2dbdca6d4586`. |
| 2026-08-24 | Final protected-data reconciliation passed | Zero mismatches for 12 user/password hashes, two cash transactions, eight compensation records, ten point entries, ten promotion activities, and 114 activity logs. The recurring-announcement date matched semantically after PostgreSQL correctly normalized SQLite's datetime-formatted value to the declared date type. |
| 2026-08-24 | ALAS live on PostgreSQL | Production PHP, queue, and scheduler containers all verified `pgsql`; PostgreSQL connectivity passed from the production image. Health returned 200, home returned the expected authentication redirect, catalog returned 200 with one product, all four v1 storefront routes were present, and recent service logs contained no database/application errors. SQLite remains preserved for rollback. |
| 2026-08-24 | PayMongo sandbox checkout enabled | Added server-only test credentials, authenticated sandbox requests between Next.js and Laravel, isolated `TEST-WEB` orders from inventory movements and finance, and redirected checkout to PayMongo Checkout Sessions. Production migration `2026_08_24_000003` completed. End-to-end test created `TEST-WEB-00000001`; product stock remained 20 and sale movements remained zero. Payment webhook reconciliation remains pending before live payments. |

## 13. Required plan-maintenance rule

At the end of every related development session:

1. Update phase checkboxes and the progress log.
2. Record schema/API decisions and newly discovered risks here.
3. Link implementation commits, migration reports, and test evidence without adding secrets or personal data.
4. Do not mark a phase complete until its exit gate is satisfied.
5. If implementation and this plan disagree, stop and resolve the discrepancy before continuing.
