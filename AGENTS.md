# LMS Gadai Mulia revamp

- Target: Laravel 13, Vue 3, Inertia, Tailwind; PHP >= 8.3, Node >= 22.12.
- User requires all legacy features and the original legacy styling (Poppins, purple accents, dark rounded sidebar).
- Legacy reference: `C:/laragon/www/lmsgadai`. Make revamp changes here, not in that application.
- User chose the SAME LMS and employee databases. Never run fresh/reset/seed against configured databases. Use SQLite memory for tests. Keep credentials out of output.
- Start with `docs/STATUS.md` for current verified state. Read `.agents/skills/lms-vilt/SKILL.md` for domain work; read only relevant references.
- Route/page map: `config/pages.php`. Domain JSON endpoints: `routes/legacy.php`, `routes/api.php`, `routes/quiz.php`.
- Use focused `rg` searches; exclude vendor, node_modules, lock files, generated build output, logs, and uploads from broad reads. Summarize checks rather than dumping output.
- Update `docs/STATUS.md` after substantive changes with verified results and remaining work. Do not duplicate architecture details there.
