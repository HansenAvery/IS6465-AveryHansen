# Lab 7 — Peer Tutoring Hub User Management

This package targets PHP 8+ with PDO SQLite and extends the supplied Lab 6 application. It retains the PHP page / service / repository / SQLite three-tier structure and the existing `students` and `tutoring_requests` data model. Lab 7 adds a `users` account table and a nullable `students.user_id` relationship so older Lab 6 student/request records remain intact.

## Implemented use cases

| Use case | Implementation |
|---|---|
| UM-01 Create User Account | `php/auth/register.php`, `services/userService.php`, `repositories/userPDO.php` — Student/Tutor only; new accounts Active |
| UM-02 View Own Account | `php/profile/view.php` — authenticated profile only |
| UM-03 View/Search User Accounts | `php/admin/users.php` — Administrator only; name/email search and role/status filters |
| UM-04 Update Own Account | `php/profile/edit.php` — name/email only; role/status read-only |
| UM-05 Change Password | `php/profile/password.php` — verifies current password and stores a new hash |
| UM-06 Update User Role | `php/admin/manage-user.php` — Administrator only; protects last active administrator |
| UM-07 Activate/Deactivate Account | `php/admin/manage-user.php` — Administrator only; protects own and last active administrator account |
| UM-08 Delete User Account | `php/admin/manage-user.php` — fail-closed block because the supplied design says retention/permanent deletion policy is unresolved |
| UM-09 Log In | `php/auth/login.php` — generic failure, active account check, `password_verify`, session ID regeneration |
| UM-10 Log Out | POST form in navigation to `php/auth/logout.php`; CSRF protected, cookie and server session cleared |

The tutoring workflow is Student-only for submission and own-request listing/edit/cancel. Administrators can view/edit/cancel any Open request. Tutors have no tutoring-request workflow in this Lab 7 version. Ownership is enforced in SQL, not merely in navigation.

## Setup / migration

**Back up your Lab 6 `database/tutoring.db` before migrating.** From the `lab7` project directory, run the one-time additive migration against the existing database:

```bash
sqlite3 database/tutoring.db < database/migrate-lab7.sql
```

The migration creates `users`, adds nullable `students.user_id`, and creates a unique index. It does not delete or rewrite existing students or tutoring requests. Legacy rows remain unlinked and are visible only to administrators until deliberately reconciled with accounts.

For a brand-new disposable database, use:

```bash
rm -f database/tutoring.db
sqlite3 database/tutoring.db < database/setup-database.sql
```

Do not run `setup-database.sql` over the existing Lab 6 database as a substitute for the migration; SQLite's `CREATE TABLE IF NOT EXISTS` will not add the missing `user_id` column.

## Create the first administrator

Public registration intentionally cannot select Administrator. After applying the migration, create the initial administrator from the project root using environment variables:

```bash
php database/create-admin.php
```

Enter the name, email, and a unique password when prompted. The password is not supplied as a command-line argument. The script refuses duplicate email addresses and is not accessible through the web router.

## Run in GitHub Codespaces

```bash
php -S 0.0.0.0:8000 php/router.php
```

Open the forwarded HTTPS port. Register a Student and a Tutor, then log in. Administrator accounts must be provisioned using the CLI command above. The application uses secure cookies automatically when HTTPS is detected; plain HTTP is suitable only for local development.

## Tests

Run the existing Lab 6 regression suite and the new user management suite:

```bash
php tests/validation-tests.php
php tests/integration-tests.php
php tests/user-management-tests.php
```

The user management test suite uses an isolated in-memory SQLite database. It covers account creation, unique email, roles, profile updates, password changes, status protection, deletion blocking, authentication service behavior, administrator search, and request ownership rules. Browser-level session/CSRF/redirect checks should also be completed manually using the acceptance checklist below.

## Security behavior

- Passwords are stored only as `password_hash(..., PASSWORD_DEFAULT)` hashes and checked with `password_verify()`. The design leaves exact password requirements open; this implementation adopts a 10-character minimum as a documented classroom default.
- Login failures use the same message for unknown, inactive, or incorrect credentials.
- The browser cookie contains only the session identifier; session data stores user ID, display name, role, activity timestamp, and CSRF token.
- Session cookies are HttpOnly, SameSite=Lax, and Secure when HTTPS is active. Session IDs regenerate after login. Idle sessions expire after 20 minutes.
- Every protected page reloads the user from SQLite and checks Active status. Role checks are server-side.
- All state-changing forms and endpoints use POST and a session-bound CSRF token.
- SQL uses prepared statements; output is HTML-escaped.
- Student tutoring queries constrain the row by the authenticated user's linked `student_id`.

## Manual acceptance checklist

1. Register Student and Tutor; verify both are Active. Try duplicate email and a crafted `role=Administrator` POST (must be rejected).
2. Log in with valid credentials; verify invalid credentials show only “Invalid email or password.”
3. Open `/php/admin/users.php` as Student or Tutor (must return 403).
4. As Student, submit a request, view it, edit it while Open, and cancel it. Attempt to change the request ID to another student's ID (must return 404).
5. As Tutor, attempt `/php/request-form.php` and `/php/requests.php` (must return 403).
6. As Administrator, search/filter accounts, change another user's role/status, and verify the last active administrator cannot be demoted/deactivated/deleted.
7. Verify UM-08 displays the policy block and changes no database rows.
8. Remove/change a form's CSRF token and submit (must return 403).
9. Wait more than 20 minutes or set the session timestamp back for a controlled timeout test; protected access must redirect to login.
10. Log out, then revisit a protected URL (must redirect to login).

## Traceability / architecture

Browser → PHP page/controller → service validation/workflow → repository PDO query → SQLite. Templates and navigation remain in `includes/`; SQL stays in `repositories/`; business rules stay in `services/`; connection setup remains in `config/db.php`.

The supplied Lab 6 database is preserved by the additive migration. `setup-database.sql` describes the full schema for clean test databases. `migrate-lab7.sql` is the correct path for an existing Lab 6 database.

## Known limitations

- UM-08 permanent deletion remains intentionally blocked pending an approved retention policy, as required by the design document's open decision.
- Existing Lab 6 student records are not automatically assigned to newly registered users. Account registration links a Student account to a matching unlinked student email or creates a student row. Administrators can review legacy rows separately; no automatic account is fabricated for them.
- The supplied environment used for package validation did not have the PHP PDO SQLite extension installed, so PHP SQLite integration tests could not be executed here. Run them in Codespaces after confirming `pdo_sqlite` is enabled.
- This is a classroom application, not a production-ready public service. Deploy only behind HTTPS with operational backups, rate limiting, monitoring, and an approved account-retention policy.
