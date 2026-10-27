<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $prefix = config('filesystems.minio_public_prefix', '');
    echo 'Configured upload prefix: '.($prefix === '' ? '(bucket root)' : $prefix.'/').PHP_EOL;
    echo 'Configuration cached: '.($app->configurationIsCached() ? 'yes' : 'no').PHP_EOL;
    foreach (['s3', 'public'] as $name) {
        config(['filesystems.disks.'.$name.'.http' => ['connect_timeout' => 5, 'timeout' => 30]]);
        config(['filesystems.disks.'.$name.'.retries' => 0]);
    }
    $disk = Illuminate\Support\Facades\Storage::disk('s3');
    $result = $disk->getClient()->listObjectsV2([
        'Bucket' => config('filesystems.disks.s3.bucket'),
        'Prefix' => $prefix === '' ? '' : $prefix.'/',
        'MaxKeys' => 1,
        '@http' => ['connect_timeout' => 5, 'timeout' => 15],
        '@retries' => 0,
    ]);
    echo 'MinIO connection: OK'.PHP_EOL;
    echo 'Configured upload prefix contains objects: '.(($result['KeyCount'] ?? 0) > 0 ? 'yes' : 'no').PHP_EOL;
    $root = $disk->getClient()->listObjectsV2([
        'Bucket' => config('filesystems.disks.s3.bucket'), 'Delimiter' => '/', 'MaxKeys' => 100,
        '@http' => ['connect_timeout' => 5, 'timeout' => 15], '@retries' => 0,
    ]);
    echo 'Top-level folders: '.implode(', ', array_column($root['CommonPrefixes'] ?? [], 'Prefix')).PHP_EOL;

    if (($result['KeyCount'] ?? 0) === 0) {
        echo 'No objects found under the configured prefix. Check MINIO_PUBLIC_PREFIX and the configuration cache.'.PHP_EOL;
        exit(1);
    }

    if (in_array('--read', $argv, true)) {
        // Select one small existing PDF. Never create an object or print its contents.
        $activePrefix = ltrim($prefix.'/aktif/', '/');
        $objects = $disk->getClient()->listObjectsV2([
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Prefix' => $activePrefix, 'MaxKeys' => 1000,
        ]);
        $sample = null;
        foreach ($objects['Contents'] ?? [] as $object) {
            if (str_ends_with(strtolower($object['Key']), '.pdf') && $object['Size'] > 0 && $object['Size'] <= 10 * 1024 * 1024) {
                $sample = $object;
                break;
            }
        }
        if ($sample === null) {
            echo 'PDF read: no PDF <= 10 MiB found in the first 1000 active objects.'.PHP_EOL;
            exit(1);
        }

        $path = $prefix === '' ? $sample['Key'] : substr($sample['Key'], strlen($prefix) + 1);
        $response = app(App\Http\Controllers\StoredFileController::class)($path);
        $bytes = 0;
        $signature = '';
        ob_start(function ($chunk) use (&$bytes, &$signature) {
            $bytes += strlen($chunk);
            $signature = substr($signature.$chunk, 0, 5);
            return '';
        }, 8192);
        try {
            $response->sendContent();
        } finally {
            ob_end_clean();
        }
        $ok = $response->getStatusCode() === 200 && $bytes === (int) $sample['Size'] && $signature === '%PDF-';
        echo 'Document controller stream: HTTP '.$response->getStatusCode().PHP_EOL;
        echo 'PDF read: '.($ok ? 'OK' : 'FAILED').' ('.$bytes.' bytes; PDF signature '.($signature === '%PDF-' ? 'valid' : 'invalid').')'.PHP_EOL;
        echo 'Document URL path: /storage/'.implode('/', array_map('rawurlencode', explode('/', $path))).PHP_EOL;
        echo 'This CLI check reads through the controller; browser access still requires login.'.PHP_EOL;
        if (!$ok) exit(1);
    }

} catch (Throwable $e) {
    // Never print SDK messages: they may contain signed URLs or credentials.
    echo 'MinIO connection: FAILED ('.get_class($e).')'.PHP_EOL;
    $message = strtolower($e->getMessage());
    $safeMessage = $e->getMessage();
    foreach (['key', 'secret', 'endpoint', 'bucket'] as $field) {
        $value = config('filesystems.disks.s3.'.$field);
        if (is_string($value) && $value !== '') {
            $safeMessage = str_replace([$value, rawurlencode($value)], '[redacted]', $safeMessage);
        }
    }
    $safeMessage = preg_replace('~https?://[^\s"<>]+~i', '[endpoint]', $safeMessage);
    echo 'Redacted detail: '.substr($safeMessage, 0, 600).PHP_EOL;
    foreach (['connection refused', 'timed out', 'could not resolve', 'php_network_getaddresses', 'certificate', 'unable to find the socket transport', 'unable to connect', 'unable to open', 'failed to open stream'] as $reason) {
        if (str_contains($message, $reason)) {
            echo 'Network diagnostic: '.$reason.PHP_EOL;
        }
    }
    if ($e instanceof Aws\Exception\AwsException) {
        echo 'HTTP status: '.$e->getStatusCode().PHP_EOL;
        echo 'S3 error code: '.$e->getAwsErrorCode().PHP_EOL;
    }
    for ($cause = $e; $cause; $cause = $cause->getPrevious()) {
        if (method_exists($cause, 'getHandlerContext')) {
            $context = $cause->getHandlerContext();
            echo 'Network error number: '.($context['errno'] ?? 'unknown').PHP_EOL;
        }
    }
    exit(1);
}
