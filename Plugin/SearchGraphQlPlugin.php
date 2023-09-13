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

    public function afterResolve($subject, $result, $args)
    {

        if (!isset($args['search'])) {
            return $result;
        }

        $searchQuery = $args['search'];
        $this->logger->info('Search event in progress...');

        if (!$searchQuery) {
            return $result;
        }

        $this->logger->info('Queuing Search event data...');

        $eventData = [
            'event_time' => time(),
            'search_query' => $searchQuery,
            'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
            'contents' => $result['products']['items']
        ];

        $this->messageQueue->publish(self::TOPIC_NAME, json_encode($eventData));

        return $result;
    }
}