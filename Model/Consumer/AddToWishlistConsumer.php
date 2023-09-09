<?php

namespace Robusta\FacebookConversions\Model\Consumer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;

class AddToWishlistConsumer
{
    protected $conversionsAPI;
    protected $logger;
    protected $productRepository;
    protected $categoryRepository;
    protected $storeManager;

    public function __construct(
        \Robusta\FacebookConversions\Services\ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    public function processMessage($message)
    {
        $eventData = json_decode($message, true);
        
        
        try {
            $sku = $eventData['sku'] ?? null;
            if (!$sku) {
                $this->logger->warning('SKU not found in the event data.');
                return;
            }

            $product = $this->productRepository->get($sku);

            $categoryIds = $product->getCategoryIds();
            $categoryName = 'Default'; 
            if (count($categoryIds)) {
                $category = $this->categoryRepository->get($categoryIds[0], $product->getStoreId());
                $categoryName = $category->getName();
            }

            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

            $finalEventData = [
                'data' => [
                    [
                        'event_name' => 'AddToWishlist',
                        'event_time' => $eventData['event_time'],
                        'user' => [
                            'email' => $eventData['email']
                        ],
                        'custom_data' => [
                            'content_name' => $product->getName(),
                            'content_category' => $categoryName,
                            'content_ids' => [(string)$product->getId()],
                            'contents' => [
                                [
                                    'id' => (string)$product->getId(),
                                    'quantity' => 1, 
                                    'item_price' => $product->getFinalPrice() 
                                ]
                            ],
                            'currency' => $currencyCode,
                            'value' => $product->getFinalPrice(),
                        ],
                    ],
                ],
            ];
            $this->conversionsAPI->sendEventToFacebook('AddToWishlist', $finalEventData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}