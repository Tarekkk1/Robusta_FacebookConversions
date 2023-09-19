<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class SearchResultPlugin
{
    const TOPIC_NAME = 'robusta.facebook.search';

    protected $logger;
    protected $storeManager;
    protected $messageQueue;

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
        $items = [];

        if (isset($result['items']) && is_array($result['items'])) {
            foreach ($result['items'] as $item) {
                if (isset($item['sku'])) {
                    $items[] = [
                        'sku' => $item['sku']
                      ];
                }
            }
        }
        if (isset($info->operation->selectionSet->selections[0]->arguments[0]->value->value)) {
            $searchQuery = $info->operation->selectionSet->selections[0]->arguments[0]->value->value;
            $this->logger->info('Extracted search query: ' . $searchQuery);
        } else {
            $this->logger->info('Search query not found in the provided structure.');
            return $result;
        }
        
    
        $eventData = [
            'event_time' => time(),
            'search_query' => $searchQuery,
            'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
            'contents' => $items
        ];
    
        $this->messageQueue->publish(self::TOPIC_NAME, json_encode($eventData));
        return $result;
    }
    

}