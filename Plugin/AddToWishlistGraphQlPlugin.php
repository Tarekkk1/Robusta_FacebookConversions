<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI;

class AddToWishlistGraphQlPlugin
{
    protected $logger;
    protected $productRepository;
    protected $conversionsAPI;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        ConversionsAPI $conversionsAPI
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->conversionsAPI = $conversionsAPI;
    }

    public function afterExecute($subject, $result, $wishlist, $wishlistItems)
    {
        if (!isset($wishlistItems['data']) || !is_array($wishlistItems['data'])) {
            $this->logger->warning('Unexpected wishlist items format.');
            return $result;
        }

        $data = $wishlistItems['data'];
        $sku = isset($data['sku']) ? $data['sku'] : null;

        if (!$sku) {
            $this->logger->warning('SKU not found in wishlist items data.');
            return $result;
        }

        $product = $this->productRepository->get($sku);
  
        if (!$product) {
            $this->logger->warning('Product with SKU ' . $sku . ' not found.');
            return $result;
        }

        try {
            $customerEmail = '';
            if ($wishlist->getCustomer() && $wishlist->getCustomer()->getEmail()) {
                $customerEmail = $wishlist->getCustomer()->getEmail();
            }

            $this->logger->info('AddToWishlist event in progress...');
            
            $eventData = [
                'data' => [
                    [
                        'event_name' => 'AddToWishlist',
                        'event_time' => time(),
                        'user' => [
                            'email' => hash('sha256', $customerEmail)
                        ],
                        'custom_data' => [
                            'product_name' => $product->getName(),
                            'product_id' => $product->getId(),
                            'price' => $product->getFinalPrice(),
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