<?php

use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WoocommerceSetting;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$expected = getenv('SALEPRO_EXPECTED_TEST_DB') ?: '';
if (!app()->environment('testing') || !in_array($expected, ['salepro_webhook_clean', 'salepro_webhook_upgrade'], true) || DB::selectOne('SELECT DATABASE() AS db')->db !== $expected) {
    exit(70);
}

$setting = WoocommerceSetting::query()->create([
    'sync_enabled' => true,
    'webhook_secret_current' => 'synthetic-concurrency-secret',
    'webhook_key_version' => 1,
]);
$directory = dirname(__DIR__, 4).'/scratch/woo-race-'.bin2hex(random_bytes(6));
mkdir($directory, 0777, true);
$worker = __DIR__.'/concurrent_webhook_worker.php';

$run = function (string $name, string $left, string $right) use ($setting, $directory, $worker): array {
    foreach (glob($directory.'/ready-*') ?: [] as $marker) {
        unlink($marker);
    }
    $delivery = 'race-'.$name;
    $processes = [];
    foreach ([$left, $right] as $index => $body) {
        $command = [PHP_BINARY, $worker, (string) $setting->id, base64_encode($body), $delivery, $directory, (string) ($index + 1)];
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, dirname(__DIR__, 4));
        $processes[] = [$process, $pipes];
        if ($index === 0) {
            $deadline = microtime(true) + 15;
            while (!is_file($directory.'/ready-1')) {
                if (microtime(true) > $deadline) {
                    throw new RuntimeException('first worker bootstrap timeout');
                }
                usleep(10000);
            }
        }
    }
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            throw new RuntimeException("worker exit {$exit}: {$stderr}");
        }
        try {
            $results[] = json_decode($stdout, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('worker produced invalid JSON: '.json_encode(['stdout' => $stdout, 'stderr' => $stderr]), 0, $exception);
        }
    }
    return [
        'race' => $name,
        'outcomes' => array_column($results, 'outcome'),
        'receipts' => WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->where('delivery_id', $delivery)->count(),
        'jobs' => array_sum(array_column($results, 'jobs')),
        'overlap' => min(array_column($results, 'finished')) > max(array_column($results, 'started')),
    ];
};

try {
    $proof = [
        $run('identical', '{"id":501,"date_modified_gmt":"2026-09-12T10:00:00Z"}', '{"id":501,"date_modified_gmt":"2026-09-12T10:00:00Z"}'),
        $run('conflict', '{"id":502,"date_modified_gmt":"2026-09-12T10:00:00Z"}', '{"id":502,"date_modified_gmt":"2026-09-12T10:01:00Z"}'),
    ];
    echo json_encode($proof, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
    foreach ($proof as $row) {
        if (!$row['overlap'] || $row['receipts'] !== 1 || $row['jobs'] !== 1) {
            exit(1);
        }
    }
} finally {
    WooCommerceWebhookReceipt::query()->where('woocommerce_setting_id', $setting->id)->delete();
    $setting->delete();
    foreach (glob($directory.'/*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($directory);
}
