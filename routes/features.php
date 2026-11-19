<?php

// Feature route files (mounted under /api with web + session.auth middleware by routes/api.php).
// Each file is owned by one feature: keep feature routes in its own file.
require __DIR__ . '/features/content-structure.php';
require __DIR__ . '/features/certificate.php';
require __DIR__ . '/features/document-download.php';
