---
name: lms-vilt
description: Maintain the LMS Gadai Mulia Laravel 13 and Inertia Vue revamp, including legacy feature parity, employee login, learning content, quizzes, and document reporting. Use for work in lmsgadai-revamp.
---

Read project-root `docs/STATUS.md` for current work before rediscovering the application. Application paths below are relative to that root. Read [references/architecture.md](references/architecture.md) only for routing, authorization, data, or module changes.

Keep the legacy application intact. The revamp shares its databases by explicit user choice; test mutations against an isolated test database. Preserve existing employee usernames, role codes, quiz attempts, content types, document audience restrictions, and upload paths.

Use Laravel page routes and Inertia navigation. Existing domain components reuse JSON endpoints; do not reintroduce a separate Vue Router. Put layout changes in `resources/js/layouts/AppLayout.vue`; keep Indonesian wording, legacy Poppins typography, purple accents, rounded cards and dark sidebar, and clear empty/error states.

For navigation changes, verify parameterized links and unsaved quiz/editor guards. For authentication, verify wrong-password rejection, session rotation, guest redirects, and role enforcement at the backend.

Search the module named by the task first. Read small relevant sections rather than full controllers or generated artifacts. Run targeted tests during edits and build for frontend changes. Record exact results and unresolved limitations in `docs/STATUS.md` so later sessions can resume without repeating exploration.
