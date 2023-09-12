<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class SearchGraphQlPlugin
{
    protected $logger;
    protected $storeManager;
    protected $messageQueue;
    const TOPIC_NAME = 'facebookconversions.search';

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
        if (!is_array($args) || !isset($args['search'])) {
            return $result;
        }

        $searchQuery = $args['search'];

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


// public function afterResolve($subject, $result, $args)
// {
//     if (!is_array($args) || !isset($args['search'])) {
//         return $result;
//     }

//     $searchQuery = $args['search'];

//     if (!$searchQuery) {
//         return $result;
//     }

//     $this->logger->info('Search event in progress...');

//     $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
//     $categoryName = 'Default'; 

//     $contentsArray = [];
//     $contents = $result['products']['items'];
//     foreach ($contents as $content) {
//         $contentsArray[] = [
//             'id' => $content['sku'],
//             'quantity' => 1,
//         ];
//     }

//     try {
//         $eventData = [
//             'data' => [
//                 [
//                     'event_name' => 'Search',
//                     'event_time' => time(),
//                     'custom_data' => [
//                         'content_category' => $categoryName,
//                         'content_ids' => array_column($contentsArray, 'id'),
//                         'content_type' => 'product',
//                         'contents' => $contentsArray,
//                         'currency' => $currencyCode,
//                         'search_string' => $searchQuery,
//                         'value' => 0,
//                     ],
//                 ],
//             ],
//         ];

//         $this->conversionsAPI->sendEventToFacebook('Search', $eventData);

//     } 
//     catch (\Exception $e) {
//         $this->logger->error($e->getMessage());
//     }

//     return $result;
// }