# LMS Gadai Mulia — VILT

Laravel 13 / Vue 3 / Inertia 2 / Tailwind CSS 3 / Vite. PHP 8.3+ and Node 22.12+.

## Run locally

Use Laragon with PHP 8.3 or newer and the ZIP extension enabled. Point `lmsgadai-revamp.test` at this application's `public` directory. The local `.env` already uses the existing LMS and employee database connections, with a separate app key, session cookie, and file cache.

On the current Windows installation, `scripts\serve.cmd` starts the application at `http://127.0.0.1:8001` and loads the project-specific PHP extensions. `scripts\check.cmd` runs the backend tests, frontend tests, and production build. These scripts do not change the system PHP configuration.

```sh
composer install
npm install
npm run build
php artisan serve --port=8001
```

When using Artisan's URL, set `APP_URL=http://127.0.0.1:8001` in `.env`. Open `/sign-in` with an existing employee account. Employee Bcrypt `$2a$` hashes are verified by PHP `password_verify`; wrong passwords are rejected and database hashes are not rewritten.

For development, run `npm run dev` alongside the PHP server. Laravel uses server routes and Inertia for page navigation; existing domain components retain their JSON API contracts.

For Docker with live local code and Vue hot reload, run `docker compose -f docker-compose.dev.yaml up -d --build` and open http://localhost:8002. See [Docker development](docs/DOCKER.md) for dependency updates and shutdown commands.

## Shared data

The same primary LMS database and PostgreSQL employee database are used by explicit request. Existing records are not reset or seeded. Do not use `migrate:fresh`, `db:wipe`, or setup scripts that run migrations against these connections.

Uploads use the private MinIO bucket configured in `.env`; see [MinIO setup](docs/MINIO.md). The revamp does not use a local upload folder or storage symlink. Existing database file paths and browser URLs are preserved. The revamp's sessions, cache, views, and logs remain local and separate.

## Feature map

- Employee login/logout and admin/trainer/student authorization.
- Course/category management, content editing, PDF/video/text/Google Form materials.
- Quiz bank, generation, editing, assignment, attempts, autosave, review and grading.
- Announcements, forms, audience filters, archive/restore, document preview and view tracking.
- Document reports, Excel exports, activity logs.
- Maintenance page with database-configured duration.

Page definitions are in `config/pages.php`; domain endpoints are in `routes/legacy.php`, `routes/api.php`, and `routes/quiz.php`. The `quiz:expire` schedule is registered in `routes/console.php`; enable Laravel's scheduler when running the app continuously.

## Checks

```sh
php -d extension=zip -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit
npm run test:frontend
npm run build
```

Tests force SQLite in memory and never initialize the configured LMS database. If SQLite is already enabled, omit the `-d` options.

## Working context

Read `AGENTS.md`, then `docs/STATUS.md`. Project guidance lives at `.agents/skills/lms-vilt/SKILL.md`; load its architecture reference only when needed. Keep status notes short and current, and search the relevant module rather than dumping the entire source tree.
