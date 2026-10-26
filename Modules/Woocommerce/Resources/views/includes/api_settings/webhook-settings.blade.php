<div class="tab-pane fade" id="list-webhook-settings" role="tabpanel" aria-labelledby="list-webhook-settings-list">
    @php($webhookConfigured = !empty($woocommerce_setting?->webhook_secret_current))
    <div class="card-body">
        <p><strong>Status:</strong> {{ $webhookConfigured ? 'Secret configured' : 'Admin repair required' }}</p>
        <p><strong>Key version:</strong> {{ (int) ($woocommerce_setting?->webhook_key_version ?? 1) }}</p>
        @if($woocommerce_setting?->webhook_previous_expires_at)
            <p><strong>Previous-secret expiry:</strong> {{ $woocommerce_setting->webhook_previous_expires_at }}</p>
        @endif
        @if($woocommerce_setting?->public_webhook_id)
            <label>Webhook URL (contains no secret)</label>
            <input class="form-control" readonly value="{{ url('/api/woocommerce/webhooks/'.$woocommerce_setting->public_webhook_id) }}">
        @endif
        <form method="POST" action="{{ route('woocommerce.webhook-secret') }}" class="mt-3">
            @csrf
            <input type="hidden" name="action" value="replace">
            <div class="form-group"><label>New webhook secret</label><input type="password" autocomplete="new-password" name="webhook_secret" class="form-control" value=""></div>
            <div class="form-group"><label>Previous-secret overlap (seconds, maximum 86400)</label><input type="number" name="rotation_overlap_seconds" min="0" max="86400" value="3600" class="form-control"></div>
            <button class="btn btn-primary" type="submit">Set or rotate secret</button>
        </form>
        <form method="POST" action="{{ route('woocommerce.webhook-secret') }}" class="mt-3" data-confirm-message="Revoke this webhook secret?">@csrf<input type="hidden" name="action" value="revoke"><button class="btn btn-warning" type="submit">Revoke secret</button></form>
        <form method="POST" action="{{ route('woocommerce.webhook-secret') }}" class="mt-2" data-confirm-message="Disconnect webhook ingestion?">@csrf<input type="hidden" name="action" value="disconnect"><button class="btn btn-danger" type="submit">Disconnect webhook</button></form>
    </div>
</div>
