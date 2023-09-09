<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Services\ConversionsAPI;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\MessageQueue\PublisherInterface;


class SearchGraphQlPlugin
{
    protected $logger;
    protected $conversionsAPI;
    protected $storeManager;
    protected $categoryRepository;
    protected $messageQueue;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConversionsAPI $conversionsAPI,
        StoreManagerInterface $storeManager,
        CategoryRepository $categoryRepository,
        PublisherInterface $messageQueue

    ) {
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
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

        $this->logger->info('Search event in progress...');

        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $categoryName = 'Default'; 

        $contentsArray = [];
        $contents = $result['products']['items'];
        foreach ($contents as $content) {
            $contentsArray[] = [
                'id' => $content['sku'],
                'quantity' => 1,
            ];
        }

        try {
            $eventData = [
                'data' => [
                    [
                        'event_name' => 'Search',
                        'event_time' => time(),
                        'custom_data' => [
                            'content_category' => $categoryName,
                            'content_ids' => array_column($contentsArray, 'id'),
                            'content_type' => 'product',
                            'contents' => $contentsArray,
                            'currency' => $currencyCode,
                            'search_string' => $searchQuery,
                            'value' => 0,
                        ],
                    ],
                ],
            ];

            $this->messageQueue->publish('facebookconversions.search', json_encode($eventData));
  
        } 
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}