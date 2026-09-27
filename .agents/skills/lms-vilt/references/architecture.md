# Architecture map

## Entry points

- `bootstrap/app.php`: Laravel 13 bootstrap, sessions/CSRF, Inertia, authorization, legacy API mount.
- `config/pages.php`: URI, route name, Vue component, title, permission, fullscreen flag.
- `resources/js/pageRegistry.js`: explicit lazy page imports. Update this registry and `config/pages.php` together when adding a page; do not glob archived/unused Vue files into the build.
- `PageController`: Inertia page props and real dashboard data.
- `resources/js/app.js`: Vite/Inertia app; Vue Query and existing component integration.
- `resources/js/navigation.js`: compatibility interface for migrated navigation; backed by Inertia, not Vue Router.
- `routes/legacy.php`, `api.php`, `quiz.php`: preserved JSON contracts.

## Data and permissions

Primary DB uses existing LMS tables. Secondary `db2` uses employee PostgreSQL `auth.users`, `master.employee`, `master.position`, `master.branch`. Credentials stay in `.env`.

Session `auth` contains `user`, `nama`, `idgrup`, `cabang`, `jabatan`, `npk`, `status`. Employee password hashes use Bcrypt `$2a$`; use `password_verify` for this external source because Laravel's Bcrypt algorithm guard expects `$2y$`. Admin position codes are configured in `config/quiz.php`. Additional roles live in `lms_users`; `LmsAccess` resolves them. `LmsAuthorization` protects page and module endpoints; quiz controllers enforce attempt ownership and quiz-specific permissions.

Domains: `CourseService` and courses/categories/course_details; `QuizService` and quiz tables; `AnnouncementService` and documents/submenu/audience relationships; `DocumentViewService` and document views/report exports. Do not replace audience filtering with an unfiltered document query.

The user requested the same database. Existing uploads must remain available through the shared configured storage root; sessions and cache are separate. No automatic data seeding or destructive migrations belong in setup.

## Verification

Backend tests: `php artisan test` (SQLite memory; enable the PHP SQLite extensions when absent). Frontend: `npm run test:frontend` and `npm run build`. Browser checks: login, dashboard, manager and student course routes, quiz editing/attempts, document filtering/reporting, maintenance, and mobile navigation. Use test mocks or isolated data for state-changing checks.


Quiz audience: QuizAudience reads/writes quiz_access_rules; AudienceDirectory provides active employee positions/branches through /api/quiz/audience-options. Session auth.idgrup and auth.cabang determine access (branch uses the final four characters, matching employee login). Empty dimension is unrestricted; populated dimensions all must match. QuizEditor/QuizAudience.vue manage these choices, including after attempts exist through visibility updates. Keep restrictions when duplicating quizzes or receiving older payloads without audience. This is employee-position filtering, not LMS-role filtering.

Course lists intentionally query fresh pagination instead of caching Eloquent/paginator objects: Laravel 13 cache.serializable_classes=false prevents restoring those objects, and the legacy app edits the same tables. Resource pagination metadata is under meta.last_page.
