<?php

return [
    // Employee position_code from the verified login session (auth.idgrup).
    'position_roles' => [
        'JBT-032' => 'admin',
        'JBT-038' => 'admin',
        'JBT-037' => 'sop',
        'JBT-039' => 'sop',
        'JBT-040' => 'sop',
        'JBT-SOP' => 'sop',
        'JBT-019' => 'trainer',
        'JBT-020' => 'sop',
        '042' => 'trainer',
        'JBT-TRAINER' => 'trainer',
    ],

    'groups' => [
        'manager' => ['admin'],
        'sop' => ['admin', 'sop'],
        'documents' => ['admin', 'sop'],
        'student' => ['student'],
    ],
];
