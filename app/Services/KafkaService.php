<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use longlang\phpkafka\Producer\Producer;
use longlang\phpkafka\Producer\ProducerConfig;

class KafkaService
{
    protected $producer;

    public function __construct()
    {
        $config = new ProducerConfig();
        $config->setBroker('warehouse.psivista.com:9092'); // Set explicit broker
        $config->setBrokers(['warehouse.psivista.com:9092']); // Set as array of brokers
        $config->setUpdateBrokers(true);
        $config->setAcks(-1);
        $this->producer = new Producer($config);
    }

    public function publishOrderPlaced($message)
    {
        try {
            $topic = config('kafka.topics.order_placed', 'facilities.orders.updated');
            
            if (is_array($message)) {
                $message = json_encode($message);
            }

            $this->producer->send($topic, $message);
            
            Log::info('Message published to Kafka', [
                'topic' => $topic,
                'message' => $message
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to publish message to Kafka', [
                'error' => $e->getMessage(),
                'message' => $message ?? null
            ]);
            
            throw $e;
        }
    }
}
