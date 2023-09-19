<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Framework\MessageQueue\PublisherInterface;

class SearchGraphQlPlugin
{
    protected $logger;
    protected $messageQueue;
    const TOPIC_NAME = 'robusta.facebook.search';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PublisherInterface $messageQueue
    ) {
        $this->logger = $logger;
        $this->messageQueue = $messageQueue;
    }

    public function afterResolve($subject, $result, $field, $context, $info, $value = null, $args = null)
    {
        $searchQuery = $args['search'] ?? '';
        try {
            $eventData = [
                'event_time' => time(),
                'search_query' => $searchQuery,
                'contents' => $result['items'] ?? []  
            ];
            $this->messageQueue->publish(self::TOPIC_NAME, json_encode($eventData));
        } catch (\Exception $e) {
            $this->logger->error('Error while queuing search event: ' . $e->getMessage());
        }
    
        return $result;
    }
    
}