<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $disk = Illuminate\Support\Facades\Storage::disk('s3');
    $result = $disk->getClient()->listObjectsV2([
        'Bucket' => config('filesystems.disks.s3.bucket'),
        'Prefix' => ($prefix = config('filesystems.minio_public_prefix', 'public')) === '' ? '' : $prefix.'/',
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
