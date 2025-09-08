<?php

return [
    'brokers' => env('KAFKA_BROKERS', 'warehouse.psivista.com:9092'),
    'consumer_group_id' => env('KAFKA_GROUP_ID', 'warehouse_group'),
    'consumer_timeout_ms' => env('KAFKA_CONSUMER_TIMEOUT_MS', 2000),

    'topics' => [
        'order_placed' => env('KAFKA_TOPIC_ORDER_PLACED', 'facilities.orders.placed'),
        'order_status' => env('KAFKA_TOPIC_ORDER_STATUS', 'warehouse.orders.status'),
        'order_approved' => env('KAFKA_TOPIC_ORDER_APPROVED', 'warehouse.orders.approved'),
        'order_rejected' => env('KAFKA_TOPIC_ORDER_REJECTED', 'warehouse.orders.rejected'),
        'order_processed' => env('KAFKA_TOPIC_ORDER_PROCESSED', 'warehouse.orders.processed'),
        'order_dispatched' => env('KAFKA_TOPIC_ORDER_DISPATCHED', 'warehouse.orders.dispatched'),
        'order_delivered' => env('KAFKA_TOPIC_ORDER_DELIVERED', 'warehouse.orders.delivered'),
    ],
];
