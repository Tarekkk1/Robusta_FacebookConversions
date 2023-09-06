<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI;

class AddToWishlistGraphQlPlugin
{
    protected $logger;
    protected $productRepository;
    protected $categoryRepository;
    protected $storeManager;
    protected $conversionsAPI;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        ConversionsAPI $conversionsAPI
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->conversionsAPI = $conversionsAPI;
    }

    public function afterExecute($subject, $result, $wishlist, $wishlistItems)
    {
        if (!isset($wishlistItems['data']) || !is_array($wishlistItems['data'])) {
            $this->logger->warning('Unexpected wishlist items format.');
            return $result;
        }

        $data = $wishlistItems['data'];
        $sku = $data['sku'] ?? null;

        if (!$sku) {
            $this->logger->warning('SKU not found in wishlist items data.');
            return $result;
        }

        try {
            $product = $this->productRepository->get($sku);

            $customerEmail = '';
            $customerEmail = $wishlist->getCustomer()->getEmail();
         

            $this->logger->info('AddToWishlist event in progress...');
            
            $categoryIds = $product->getCategoryIds();
            $categoryName = 'Default'; 
            if (count($categoryIds)) {
                $category = $this->categoryRepository->get($categoryIds[0], $product->getStoreId());
                $categoryName = $category->getName();
            }

            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

            $eventData = [
                'data' => [
                    [
                        'event_name' => 'AddToWishlist',
                        'event_time' => time(),
                        'user' => [
                            'email' => hash('sha256', $customerEmail)
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

            $this->conversionsAPI->sendEventToFacebook('AddToWishlist', $eventData);
        } 
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}