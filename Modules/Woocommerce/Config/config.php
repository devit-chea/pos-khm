<?php

return [
    'name' => 'Woocommerce',
    'webhooks' => [
        'max_payload_bytes' => 1048576,
        'json_depth' => 32,
        'requests_per_minute' => 120,
        'connection_requests_per_minute' => 90,
        'processing_timeout_seconds' => 300,
        'stale_heartbeat_seconds' => 900,
        'recovery_batch_size' => 50,
        'max_dispatch_attempts' => 5,
        'supported_topics' => [
            'order.created',
            'order.updated',
            'order.deleted',
            'order.restored',
        ],
    ],
];
