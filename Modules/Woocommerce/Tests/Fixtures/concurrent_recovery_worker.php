<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Modules\Woocommerce\Jobs\ProcessWooCommerceWebhookReceipt;
use Modules\Woocommerce\Models\WooCommerceWebhookReceipt;
use Modules\Woocommerce\Services\Webhooks\WebhookReceiptRecovery;
use Modules\Woocommerce\Services\Webhooks\WooCommerceWebhookIntake;
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app=require dirname(__DIR__, 4).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$expected=getenv('SALEPRO_EXPECTED_TEST_DB')?:'';if(!app()->environment('testing')||!in_array($expected,['salepro_webhook_clean','salepro_webhook_upgrade'],true)||DB::selectOne('SELECT DATABASE() AS db')->db!==$expected){exit(70);}
[$script,$receiptId,$mode,$barrier,$workerId]=$argv;config(['woocommerce.webhooks.connection_requests_per_minute'=>1000,'woocommerce.webhooks.supported_topics'=>['order.updated']]);Queue::fake();
$receipt=WooCommerceWebhookReceipt::query()->with('setting')->findOrFail((int)$receiptId);file_put_contents($barrier.'/ready-'.$workerId,'1',LOCK_EX);$deadline=microtime(true)+10;while(count(glob($barrier.'/ready-*'))<2){if(microtime(true)>$deadline)exit(72);usleep(10000);}
$started=microtime(true);
if($mode==='recover'){$outcome=app(WebhookReceiptRecovery::class)->redispatch($receipt)?'recovered':'not_claimed';}
else{$body=$receipt->payload;$request=Request::create('/api/woocommerce/webhooks/'.$receipt->setting->public_webhook_id,'POST',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_WC_WEBHOOK_DELIVERY_ID'=>$receipt->delivery_id,'HTTP_X_WC_WEBHOOK_TOPIC'=>'order.updated','HTTP_X_WC_WEBHOOK_RESOURCE'=>'order','HTTP_X_WC_WEBHOOK_EVENT'=>'updated','HTTP_X_WC_WEBHOOK_SIGNATURE'=>base64_encode(hash_hmac('sha256',$body,$receipt->setting->webhook_secret_current,true))],$body);$outcome=app(WooCommerceWebhookIntake::class)->receive($request,$receipt->setting->public_webhook_id)['outcome'];}
echo json_encode(['outcome'=>$outcome,'jobs'=>Queue::pushed(ProcessWooCommerceWebhookReceipt::class)->count(),'started'=>$started,'finished'=>microtime(true)],JSON_THROW_ON_ERROR);
