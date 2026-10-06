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
    echo 'Active storage mode: '.config('filesystems.lms_storage_driver').PHP_EOL;
    $root = $disk->getClient()->listObjectsV2([
        'Bucket' => config('filesystems.disks.s3.bucket'), 'Delimiter' => '/', 'MaxKeys' => 100,
        '@http' => ['connect_timeout' => 5, 'timeout' => 15], '@retries' => 0,
    ]);
    echo 'Top-level folders: '.implode(', ', array_column($root['CommonPrefixes'] ?? [], 'Prefix')).PHP_EOL;
    if (in_array('--verify-legacy', $argv, true)) {
        $localRoot = env('LMS_UPLOAD_ROOT');
        if (!$localRoot || !is_dir($localRoot)) {
            throw new RuntimeException('Local legacy upload directory is unavailable.');
        }
        $objects = [];
        $pages = $disk->getClient()->getPaginator('ListObjectsV2', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Prefix' => $prefix === '' ? '' : $prefix.'/',
            '@http' => ['connect_timeout' => 5, 'timeout' => 30], '@retries' => 0,
        ]);
        foreach ($pages as $page) {
            foreach ($page['Contents'] ?? [] as $object) {
                $objects[$object['Key']] = (int) $object['Size'];
            }
        }
        $count = $missing = $different = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($localRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file->isFile() || $file->getFilename() === '.gitignore') continue;
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($localRoot, '/\\')) + 1));
            $key = ltrim($prefix.'/'.$relative, '/');
            $count++;
            if (!array_key_exists($key, $objects)) {
                if (in_array('--copy-missing', $argv, true)) {
                    $stream = fopen($file->getPathname(), 'rb');
                    try {
                        $disk->getClient()->putObject([
                            'Bucket' => config('filesystems.disks.s3.bucket'), 'Key' => $key,
                            'Body' => $stream, 'ContentLength' => $file->getSize(),
                            'ContentType' => mime_content_type($file->getPathname()) ?: 'application/octet-stream',
                            'IfNoneMatch' => '*',
                            '@http' => ['connect_timeout' => 5, 'timeout' => 120], '@retries' => 0,
                        ]);
                        echo 'Copied missing object: '.$key.PHP_EOL;
                    } finally {
                        fclose($stream);
                    }
                } else {
                    $missing++;
                    if ($missing <= 20) echo 'Missing object: '.$key.PHP_EOL;
                }
            }
            elseif ($objects[$key] !== $file->getSize()) $different++;
        }
        echo "Legacy comparison: files=$count missing=$missing size_mismatch=$different".PHP_EOL;
        if ($missing || $different) exit(2);
        $sample = array_key_first(array_filter($objects, fn ($size) => $size > 0));
        if ($sample !== null) {
            $disk->getClient()->getObject([
                'Bucket' => config('filesystems.disks.s3.bucket'), 'Key' => $sample, 'Range' => 'bytes=0-31',
                '@http' => ['connect_timeout' => 5, 'timeout' => 15], '@retries' => 0,
            ]);
            echo 'Sample object read: OK'.PHP_EOL;
        }
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
