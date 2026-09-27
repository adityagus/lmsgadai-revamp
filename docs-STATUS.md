# Revamp status

Target: C:/laragon/www/lmsgadai-revamp. Original application: ../lmsgadai.

User decisions: preserve all features; Laravel 13 + Vue/Inertia/Tailwind; same primary LMS and secondary employee databases; clean UI; project skill and compact context notes.

Implemented source: Laravel 13 bootstrap, legacy domain modules/API contracts, Inertia route map and navigation, login password validation, backend role guards, new shell/login/dashboard, maintenance view and DB estimate, isolated app session/cache identity, shared upload root, project skill.

Verified 2026-09-25:
- Laravel 13.33.0; Inertia Laravel 2.0.27 / Vue adapter 2.3.28; Vue 3; Tailwind 3; Vite 6.
- Backend: 40 tests passed, 769 assertions (SQLite memory). Includes login, roles, Inertia page mapping, dashboard counts, quiz flows, maintenance fallback, and document archive/restore.
- Frontend: 26 tests passed. Production Vite build passed. CKEditor remains a large lazy-loaded chunk (~1.46 MB uncompressed).
- Browser: actual login renders; dashboard checked with synthetic data. Mobile viewport 390 px has no horizontal overflow; sidebar opens; no JavaScript exceptions observed. Full live-data workflows were not exercised through an employee account.
- Employee database hashes are Bcrypt `$2a$`. Native `password_verify` fixes Laravel's `$2y$` guard error; valid/invalid password tests pass. Hashes were not rewritten.
- Shared public upload link is created. Local/public/aktif disks share the original upload location so archive and restore remain consistent. Session/cache/log directories remain separate.
- Official skill validator passes. `.agents/skills/lms-vilt` contains the workflow and an on-demand architecture reference.

Run locally with `scripts\serve.cmd` (http://127.0.0.1:8001); verify with `scripts\check.cmd`. Scripts load ZIP/SQLite extensions through `.runtime/php`. Laragon's separate web server needs ZIP enabled for Excel export. The app server was started during verification.

Initial revamp used the existing schema without migrations or resets; the later additive quiz-access migration is recorded below. Three conflicting new-project scaffold migrations were removed; existing migration history is retained. All application changes are in the revamp folder, not the legacy application.

Styling update: user requested matching the original app. Legacy component templates/styles, Poppins, purple palette, Sidebar/Header, login composition and dashboard composition restored. Inertia navigation, password verification and shared database protections retained. Verified production build and browser rendering at 1440px and 390px: login, dashboard, sidebar toggle, classes and quiz editor; no horizontal overflow or JavaScript exceptions. Legacy assistant widget preserves its appearance but external chat is disabled pending secure server-side configuration; no embedded API credential is bundled.


Class management and quiz audience update (2026-09-25):
- Fixed course-list 500: Laravel 13 cache forbids deserializing paginator objects. Courses now use fresh ordered pagination, avoiding stale shared-DB data. Frontend accepts Laravel resource meta.last_page; manager controls use resolved LMS roles and the correct read-only prop.
- User clarified quiz access must use employee POSITION (jabatan), not LMS role, plus branch. Editor has searchable multi-select checkboxes; empty dimension means unrestricted, selections within each dimension are OR, position and branch constraints combine with AND. Existing course/class assignment rules remain required.
- quiz_access_rules stores restrictions. Save/update/visibility and duplication preserve rules; students cannot edit rules. Enforcement covers quiz listings, start/resume, attempt reads, answers and submission, including linked course quizzes. Missing employee identity fields fail a configured restriction. Existing attempts/results are retained.
- Ran ONLY database/migrations/2026_09_25_000002_create_quiz_access_rules.php against shared LMS DB; it creates one new table, without rewriting existing tables/data. Legacy app code remains unchanged; restrictions are enforced by revamp endpoints.
- Verification: focused backend 27 tests / 361 assertions (SQLite memory), frontend 26 tests, Vite production build passed. Live read-only checks serialized course pages repeatedly and loaded 47 positions / 38 branches. Browser used synthetic data to check class list and quiz editor on desktop/mobile, selection controls, legacy styling and sidebar; no employee account mutations performed.

Course audience update (2026-09-25): Course managers can select employee positions in the create/edit form. `course_access_rules` stores selected positions; an empty selection keeps a course open to all. Student course listings, course detail, content listings, and previews enforce selected positions server-side, while managers retain administrative access. The additive migration `2026_09_25_000003_create_course_access_rules.php` was run against the shared LMS database without rewriting existing data. The course/Inertia checks pass: 7 tests and 430 assertions.
