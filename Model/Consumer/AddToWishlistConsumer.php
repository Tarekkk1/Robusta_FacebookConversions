<?php

namespace Robusta\FacebookConversions\Model\Consumer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\WishlistFactory;

class AddToWishlistConsumer
{
    protected $conversionsAPI;
    protected $logger;
    protected $productRepository;
    protected $categoryRepository;
    protected $storeManager;
    protected $wishlistFactory;
    protected $customerRepository;

    public function __construct(
        \Robusta\FacebookConversions\Services\ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        WishlistFactory $wishlistFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
        
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->wishlistFactory = $wishlistFactory;
        $this->customerRepository = $customerRepository;
    }
    public function processMessage($message)
    {
        $eventData = json_decode($message, true);
        $productId = $eventData['product_id'];
        $wishlistId = $eventData['wishlist_id'];
    
        try {
            $product = $this->productRepository->getById($productId);
            $categoryIds = $product->getCategoryIds();
            $categoryNames = [];  
    
            if (is_array($categoryIds) && count($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    try {
                        $category = $this->categoryRepository->get($categoryId, $product->getStoreId());
                        $categoryNames[] = $category->getName();
                    } catch (\Exception $e) {
                        $this->logger->error('Error fetching category with ID ' . $categoryId . ': ' . $e->getMessage());
                    }
                }
            }
    
            $categoriesAsString = !empty($categoryNames) ? implode(', ', $categoryNames) : 'Default';

            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

            $wishlist = $this->wishlistFactory->create()->load($wishlistId);
            
            $customerId = $wishlist->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            $customerEmail = $customer->getEmail();
         
            $finalEventData = [
                'event_name' => 'AddToWishlist',
                'event_time' => $eventData['event_time'],
                'user' => [
                    'email' => hash('sha256', $customerEmail),
                ],
                'custom_data' => [
                    'content_name' => $product->getName(),
                    'content_category' => $categoriesAsString,
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
    
            $this->conversionsAPI->sendEventToFacebook('AddToWishlist', ['data' => [$finalEventData]]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
    
}