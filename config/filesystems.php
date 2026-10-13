<?php

$publicPrefix = trim((string) env('MINIO_PUBLIC_PREFIX', 'public'), '/');
$s3 = [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID') ?: env('MINIO_ACCESS_KEY'),
    'secret' => env('AWS_SECRET_ACCESS_KEY') ?: env('MINIO_SECRET_KEY'),
    'region' => env('AWS_DEFAULT_REGION') ?: env('MINIO_REGION', 'us-east-1'),
    'bucket' => env('AWS_BUCKET') ?: env('MINIO_BUCKET'),
    'endpoint' => env('AWS_ENDPOINT') ?: env('MINIO_ENDPOINT'),
    'use_path_style_endpoint' => env('MINIO_ENDPOINT')
        ? env('MINIO_USE_PATH_STYLE_ENDPOINT', true)
        : env('AWS_USE_PATH_STYLE_ENDPOINT', true),
    'visibility' => 'private',
    'stream_reads' => true,
    'throw' => true,
];

return [

    'default' => 's3',
    'minio_public_prefix' => $publicPrefix,

    'disks' => [

        'public' => [...$s3, 'root' => $publicPrefix],
        'aktif' => [...$s3, 'root' => ltrim($publicPrefix.'/aktif', '/')],

        's3' => $s3,

    ],

    'links' => [],

];
