# End-to-end tests

Run the full browser suite with:

```bash
npm run test:e2e
```

The suite creates and migrates `database/e2e.sqlite`, a dedicated SQLite database ignored by Git. It never migrates, seeds, or deletes the development or production database.

Coverage includes sign-in, desktop/mobile responsive login, role boundaries, owner page access, manager inventory controls, staff data scope, staff order submission, and manager approval.
