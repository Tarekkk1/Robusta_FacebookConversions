<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class AddToWishlistGraphQlPlugin
{
    protected $logger;
    protected $productRepository;
    protected $storeManager;
    protected $publisher;

    const TOPIC_NAME = 'robusta.facebook.addtowishlist';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->publisher = $publisher; 
    }

    public function afterResolve($subject, $result, $wishlist, $wishlistItems)
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

        $eventData = [
            'event_time' => time(),
            'sku' => $sku,
            'email' => hash('sha256', $wishlist->getCustomer()->getEmail()),
            
        ];

        $this->publisher->publish(self::TOPIC_NAME, json_encode($eventData));
        return $result;
    }
}
 // public function afterResolve($subject, $result, $wishlist, $wishlistItems)
    // {
    //     if (!isset($wishlistItems['data']) || !is_array($wishlistItems['data'])) {
    //         $this->logger->warning('Unexpected wishlist items format.');
    //         return $result;
    //     }

    //     $data = $wishlistItems['data'];
    //     $sku = $data['sku'] ?? null;

    //     if (!$sku) {
    //         $this->logger->warning('SKU not found in wishlist items data.');
    //         return $result;
    //     }

    //     try {
    //         $product = $this->productRepository->get($sku);
    //         $customerEmail = $wishlist->getCustomer()->getEmail();
         

    //         $this->logger->info('AddToWishlist event in progress...');
            
    //         $categoryIds = $product->getCategoryIds();
    //         $categoryName = 'Default'; 
    //         if (count($categoryIds)) {
    //             $category = $this->categoryRepository->get($categoryIds[0], $product->getStoreId());
    //             $categoryName = $category->getName();
    //         }

    //         $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

    //         $eventData = [
    //             'data' => [
    //                 [
    //                     'event_name' => 'AddToWishlist',
    //                     'event_time' => time(),
    //                     'user' => [
    //                         'email' => hash('sha256', $customerEmail)
    //                     ],
    //                     'custom_data' => [
    //                         'content_name' => $product->getName(),
    //                         'content_category' => $categoryName,
    //                         'content_ids' => [(string)$product->getId()],
    //                         'contents' => [
    //                             [
    //                                 'id' => (string)$product->getId(),
    //                                 'quantity' => 1, 
    //                                 'item_price' => $product->getFinalPrice() 
    //                             ]
    //                         ],
    //                         'currency' => $currencyCode,
    //                         'value' => $product->getFinalPrice(),
    //                     ],
    //                 ],
    //             ],
    //         ];

    //         $this->conversionsAPI->sendEventToFacebook('AddToWishlist', $eventData);
    //     } 
    //     catch (\Exception $e) {
    //         $this->logger->error($e->getMessage());
    //     }

    //     return $result;
    // }