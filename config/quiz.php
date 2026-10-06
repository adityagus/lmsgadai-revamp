<?php

return [
    // Compatibility setting; position-to-role mapping lives in config/access.php.
    'admin_positions' => array_keys(array_filter(
        config('access.position_roles', []),
        fn ($role) => $role === 'admin',
    )),
];
