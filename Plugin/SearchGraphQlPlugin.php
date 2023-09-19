<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class SearchGraphQlPlugin
{
    protected $logger;
    protected $storeManager;
    protected $messageQueue;
    const TOPIC_NAME = 'robusta.facebook.search';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        PublisherInterface $messageQueue
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->messageQueue = $messageQueue;
    }

    public function afterResolve($subject, $result, $field, $context, $info, $value = null, $args = null)
      
    {
        if (isset($info->operation->selectionSet->selections[0]->arguments[0]->value->value)) {
            $searchQuery = $info->operation->selectionSet->selections[0]->arguments[0]->value->value;
            $this->logger->info('Extracted search query: ' . $searchQuery);
        } else {
            $this->logger->info('Search query not found in the provided structure.');
            return $result;
        }
    
        try {
            $eventData = [
                'event_time' => time(),
                'search_query' => $searchQuery,
                'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                'contents' => $result['items'] ?? []  
            ];
    
            $this->logger->info(json_encode($result['items']));
            $this->messageQueue->publish(self::TOPIC_NAME, json_encode($eventData));
        } catch (\Exception $e) {
            $this->logger->error('Error while queuing search event: ' . $e->getMessage());
        }
    
        return $result;
    }
    
}