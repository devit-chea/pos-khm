<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Modules\Woocommerce\Jobs\ProcessWooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Services\Webhooks\WooCommerceWebhookIntake;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expected = getenv('SALEPRO_EXPECTED_TEST_DB') ?: '';
if (!app()->environment('testing') || !in_array($expected, ['salepro_webhook_clean', 'salepro_webhook_upgrade'], true) || DB::selectOne('SELECT DATABASE() AS db')->db !== $expected) {
    fwrite(STDERR, "unsafe environment\n");
    exit(70);
}

[$script, $settingId, $encodedBody, $deliveryId, $barrierDirectory, $workerId] = $argv;
$setting = WoocommerceSetting::query()->findOrFail((int) $settingId);
$body = base64_decode($encodedBody, true);
if (!is_string($body)) {
    exit(71);
}

config([
    'woocommerce.webhooks.max_payload_bytes' => 1048576,
    'woocommerce.webhooks.json_depth' => 32,
    'woocommerce.webhooks.connection_requests_per_minute' => 1000,
    'woocommerce.webhooks.supported_topics' => ['order.updated'],
]);
Queue::fake();

file_put_contents($barrierDirectory.DIRECTORY_SEPARATOR.'ready-'.$workerId, '1', LOCK_EX);
$deadline = microtime(true) + 10;
while (count(glob($barrierDirectory.DIRECTORY_SEPARATOR.'ready-*')) < 2) {
    if (microtime(true) > $deadline) {
        fwrite(STDERR, "barrier timeout\n");
        exit(72);
    }
    usleep(10000);
}

$request = Request::create('/api/woocommerce/webhooks/'.$setting->public_webhook_id, 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => $deliveryId,
    'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.updated',
    'HTTP_X_WC_WEBHOOK_RESOURCE' => 'order',
    'HTTP_X_WC_WEBHOOK_EVENT' => 'updated',
    'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $setting->webhook_secret_current, true)),
], $body);

$started = microtime(true);
$result = app(WooCommerceWebhookIntake::class)->receive($request, $setting->public_webhook_id);
echo json_encode([
    'outcome' => $result['outcome'],
    'receipt_id' => $result['receipt_id'],
    'jobs' => Queue::pushed(ProcessWooCommerceWebhookReceipt::class)->count(),
    'started' => $started,
    'finished' => microtime(true),
], JSON_THROW_ON_ERROR);
