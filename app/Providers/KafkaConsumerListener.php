<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Consumer\ConsumerConfig;
use App\Events\OrderEvent;

class KafkaConsumerListener
{
    protected $consumer;
    protected $isRunning = true;

    public function __construct()
    {
        $config = new ConsumerConfig();
        $config->setBrokers(['warehouse.psivista.com:9092']);
        $config->setTopic('facilities.orders.updated');
        $config->setGroupId('warehouse-consumer-group');
        $config->setClientId('warehouse-consumer-' . uniqid());
        $config->setGroupInstanceId('warehouse-1');
        $config->setAutoCommit(true);
        $config->setInterval(0.1);
        
        $this->consumer = new Consumer($config);
    }

    public function handle()
    {
        try {
            while ($this->isRunning) {
                $message = $this->consumer->consume();
                
                if ($message) {
                    $data = json_decode($message->getValue(), true);
                    Log::info('Received message from Kafka', [
                        'topic' => $message->getTopic(),
                        'data' => $data
                    ]);

                    if (isset($data['order_id'])) {
                        // Using existing OrderEvent instead of OrderUpdatedFromFacility
                        event(new OrderEvent('Updated from facility'));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Kafka Consumer Error: ' . $e->getMessage());
        }
    }
}
