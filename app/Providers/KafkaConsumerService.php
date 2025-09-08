<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Consumer\ConsumerConfig;

class KafkaConsumerService
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

    public function consume()
    {
        try {
            while ($this->isRunning) {
                $message = $this->consumer->consume();
                
                if ($message) {
                    $data = json_decode($message->getValue(), true);
                    Log::info('Received message from Kafka', [
                        'topic' => $message->getTopic(),
                        'partition' => $message->getPartition(),
                        'offset' => $message->getOffset(),
                        'data' => $data
                    ]);

                    // Process the message
                    $this->processMessage($data);
                }
            }
        } catch (\Exception $e) {
            Log::error('Kafka Consumer Error: ' . $e->getMessage());
        } finally {
            $this->consumer->close();
        }
    }

    protected function processMessage($data)
    {
        try {
            // Handle the order update message
            if (isset($data['order_id'])) {
                // Update the order status in the warehouse system
                // You might want to dispatch a job or event here
                event(new \App\Events\OrderUpdatedFromFacility($data));
            }
        } catch (\Exception $e) {
            Log::error('Error processing Kafka message: ' . $e->getMessage(), [
                'data' => $data
            ]);
        }
    }

    public function stop()
    {
        $this->isRunning = false;
    }
}
