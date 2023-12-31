<?php

namespace Robusta\FacebookConversions\Model\Consumer;

use Robusta\FacebookConversions\Services\ConversionsAPI;
use Psr\Log\LoggerInterface;

class SearchConsumer
{
    protected $conversionsAPI;
    protected $logger;
    protected $storeManager;

    public function __construct(
        ConversionsAPI $conversionsAPI,
        LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    public function processMessage($message)
    {
        $data = json_decode($message, true);
        
        $contentsArray = [];
        foreach ($data['contents'] as $content) {
            $contentsArray[] = [
                'id' => $content['sku'],
                'quantity' => 1,
            ];
        }

        $eventData = [
            'data' => [
                [
                    'event_name' => 'Search',
                    'event_time' => $data['event_time'],
                    'custom_data' => [
                        'content_category' => 'Default',
                        'content_ids' => array_column($contentsArray, 'id'),
                        'content_type' => 'product',
                        'contents' => $contentsArray,
                        'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                        'search_string' => $data['search_query'],
                        'value' => 0,
                    ],
                ],
            ],
        ];

        try {
            $this->logger->info('Processing Search event...');
            $this->conversionsAPI->sendEventToFacebook('Search', $eventData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}