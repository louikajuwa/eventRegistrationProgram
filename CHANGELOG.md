# Changelog

## [Unreleased] - 2026-10-17

### Upgrade notes

- Run `make migrate` after upgrading. The new unique `(event_id, email)` index requires that no duplicate pairs exist.

### Fixed
- **Capacity overselling.** Status, capacity and duplicate checks now run under a row lock in the same transaction as the insert.
- **Seat counting.** Inactive registrations no longer count toward capacity, the dashboards or the live counter.
- **Duplicate registrations.** Duplicates (same email, case-insensitive) for the same event are rejected with a clear message.
- **Ended events.** Events that have ended stop accepting registrations. The public page shows "Registration closed" with the reason.
- **Super admin event creation.** It returned a 500 and now returns a 403 with an explanation.
- **Registrations without an email.** These are stored as `null` instead of `''`, and no confirmation is sent to an empty address.
- **Choice questions with no options.** These no longer silently block registration; the form builder rejects them.
- **Slug and reference races.** A collision now retries instead of returning a 500.
- **Deactivated users.** They are signed out on their next request.
- **SMTP settings.** One organisation's SMTP settings no longer leak into the next queued email.
- **Public form errors.** Validation errors now highlight the right field, and non-field errors appear in a form-level box.
- **Form builder and email editor.** These now show validation errors inline.
- **Table "Retry".** The button works.
- **"Copy link".** It reports failure honestly.
- **Event modal.** It no longer reopens with stale errors.
- **Headings.** There is now one `<h1>` per page.

### Security
- **HTML escaping.** Added a shared, quote-safe `esc()` and `safeUrl()` in `public/assets/js/core/util.js`. This closes attribute-injection XSS in the form builder and the notification links.
- **Confirm dialog.** It escapes its title and message.
- **Spreadsheet exports.** CSV/XLSX exports neutralise formula injection (`App\Support\SpreadsheetSanitizer`).
- **Rate limits.** Added to register (5/min), forgot-password (5/min) and the public thanks page (30/min).
- **Email changes.** Changing the account email requires the current password.
- **Question sync.** Ownership is checked before validation (404 for other organisations).
- **Environment name.** It was removed from the UI.
- **`.env.example`.** It defaults to `APP_DEBUG=false`.
- **Dependencies.** Updated to clear all `composer audit` advisories:
  - laravel/framework 13.37.0
  - guzzle 8.2.0
  - dompdf 3.1.6
  - maatwebsite/excel 3.1.70
  - phpspreadsheet 1.30.7
  - league/commonmark 2.10.3
  - symfony/http-foundation 8.1.9
  - and transitive dependencies
- **Docker.**
  - Dev tool ports are bound to loopback.
  - The MySQL healthcheck no longer exposes the password in the command line.

### UI/UX and accessibility
- **Responsive layout.** KPI grids and the form builder are responsive, and row actions and the page header wrap.
- **Mobile drawer.** Added a backdrop and close on Esc, outside click or link click, with `inert` and `aria-expanded`.
- **Page structure.** Added a skip link, `<main>` landmark, live toast region and `aria-current` navigation.
- **Dialogs.** Modals and confirm dialogs have labels, a focus trap and focus restore.
- **Tables.** Sortable headers are keyboard-operable with `aria-sort`, and pagination is semantic.
- **Labels and fields.**
  - Labels or `aria-label` on all filters and row controls.
  - `fieldset`/`legend` for choice groups.
  - Required markers.
  - `aria-invalid` and `role="alert"` on errors.
- **Contrast.** Improved for text and for primary and danger buttons, with visible focus rings.
- **Confirmations.** Added before setting a registration inactive and before deleting a builder question, plus an unsaved-changes warning in the form builder.
- **Thanks page.** It handles registrations without an email.
- **Dates.** One date format is used across the JS tables.
- **QA fixes.** The registrations status select no longer clips, the notifications sticky column keeps the unread highlight, and the topbar fits better on small phones.

### Performance
- **Pagination.** The organisation detail and organisation event pages are paginated (25 per page).
- **Socket connection.** The event dashboard reuses the global Socket.IO connection instead of opening a second one.

### Refactoring and cleanup
- **URLs in JS.** These now come from `route()` via `window.APP.routes` instead of hardcoded paths.
- **Removed.**
  - Unused `searchable-select.js` and the stale "partner" comments.
  - Placeholder `ExampleTest`.
- **Pint.** It passes on all files; the 4 pre-existing violations are fixed.

### Tests
- **Legacy tests.** 14 test files from another project (MLM) moved to `tests/_legacy/`, which is excluded from the suites.
- **New tests.** Added feature tests for public registration, tenant isolation, event management, admin registrations, report export and auth hardening, plus unit tests for `Event` and the spreadsheet sanitizer. The suite has 71 tests, all passing.
- **Factories.** Added factories for Organization, Event, EventQuestion and Registration.

### Infrastructure
- **Healthchecks and restart policy.** Added for `app` (php-fpm TCP) and `nginx` (`/up`), along with `restart: unless-stopped`.
- **Mailpit.** The image is pinned to `v1.30.3`.
- **`.gitignore`.** Ignores local QA tool logs and `storage/backups/*`.
- **Version control.** The project is now a git repository, with baseline commit `9025c3e`.
