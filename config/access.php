<?php

return [
    // Employee position_code from the verified login session (auth.idgrup).
    'position_roles' => [
        'JBT-032' => 'admin',
        'JBT-038' => 'trainer',
        'JBT-037' => 'sop',
        'JBT-039' => 'sop',
        'JBT-040' => 'sop',
        'JBT-SOP' => 'sop',
        'JBT-019' => 'trainer',
        'JBT-020' => 'audit',
        'JBT-047' => 'admin',
        '042' => 'trainer',
        'JBT-TRAINER' => 'trainer',
    ],

    'groups' => [
        'admin' => ['admin'],
        'instructor' => ['admin', 'sop', 'trainer'],
        'sop' => ['admin', 'sop'],
        'report' => ['admin', 'sop', 'audit'],
        'guest' => ['student'],
    ],
];
