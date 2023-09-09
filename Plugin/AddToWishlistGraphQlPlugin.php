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

    const TOPIC_NAME = 'facebookconversions.addtowishlist';

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

        $eventData = [
            'event_time' => time(),
            'sku' => $sku,
            'email' => hash('sha256', $wishlist->getCustomer()->getEmail()),
            
        ];

        $this->publisher->publish(self::TOPIC_NAME, json_encode($eventData));
        return $result;
    }
}