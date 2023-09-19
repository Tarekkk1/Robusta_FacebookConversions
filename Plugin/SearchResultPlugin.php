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
        if (isset($result['data']['search']['magento_catalog_product']['items'])) {
            $items = $result['data']['search']['magento_catalog_product']['items'];
            $this->logger->info('Found items: ' . json_encode($items));
        }

        $eventData = [
            'event_time' => time(),
            'search_query' => $args['query'],
            'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
            'contents' => $items
        ];

        $this->logger->info('Mirasvit Search event data: ' . json_encode($eventData));

        $this->logger->info('Queuing Mirasvit Search event data...');
        $this->messageQueue->publish(self::TOPIC_NAME, json_encode($eventData));

        return $result;
    }

}