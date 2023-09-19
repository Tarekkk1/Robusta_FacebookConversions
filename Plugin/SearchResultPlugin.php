<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Framework\MessageQueue\PublisherInterface;

class SearchResultPlugin
{
    const TOPIC_NAME = 'robusta.facebook.search';

    protected $logger;
    protected $messageQueue;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PublisherInterface $messageQueue
    ) {
        $this->logger = $logger;
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
        } else {
            $this->logger->info('Search query not found in the provided structure.');
            return $result;
        }
        
    
        $eventData = [
            'event_time' => time(),
            'search_query' => $searchQuery,
            'contents' => $items
        ];
    
        $this->messageQueue->publish(self::TOPIC_NAME, json_encode($eventData));
        return $result;
    }
    

}