<?php

namespace Robusta\FacebookConversions\Model\Consumer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\WishlistFactory;

class WishlistGraphqlConsumer
{
    protected $conversionsAPI;
    protected $logger;
    protected $productRepository;
    protected $categoryRepository;
    protected $storeManager;
    protected $wishlistFactory;

    public function __construct(
        \Robusta\FacebookConversions\Services\ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        WishlistFactory $wishlistFactory
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->wishlistFactory = $wishlistFactory;
    }

    public function processMessage($message)
    {
        $eventData = json_decode($message, true);

        try {
            $wishlistId = $eventData['wishlist_id'];
            $wishlist = $this->wishlistFactory->create()->load($wishlistId);
            
            $items = $wishlist->getItemCollection();
            
            $eventsData = [];
            foreach ($items as $wishlistItem) {
                $product = $wishlistItem->getProduct();
                $categoryIds = $product->getCategoryIds();
                $categoryName = 'Default'; 
                if (count($categoryIds)) {
                    $category = $this->categoryRepository->get($categoryIds[0], $product->getStoreId());
                    $categoryName = $category->getName();
                }

                $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

                $finalEventData = [
                    'event_name' => 'AddToWishlist',
                    'event_time' => $eventData['event_time'],
                    'user' => [
                        'email' => hash('sha256', $wishlist->getCustomer()->getEmail())
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
                ];
                $eventsData[] = $finalEventData;
            }

            if ($eventsData) {
                $this->conversionsAPI->sendEventToFacebook('AddToWishlist', ['data' => $eventsData]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}