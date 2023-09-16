<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class WishlistGraphqlPlugin
{
    protected $logger;
    protected $productRepository;
    protected $publisher;

    const TOPIC_NAME = 'robusta.facebook.addtowishlist';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->publisher = $publisher; 
    }

    public function afterResolve($subject, $result)
    {
        foreach ($result as $item) {
            $product = $item['model'] ?? null;
            if ($product && $product->getId()) {
                $eventData = [
                    'event_time' => time(),
                    'sku' => $product->getSku(),
                    'email' => hash('sha256', $wishlist->getCustomer()->getEmail()),
                    
                ];

                $this->publisher->publish(self::TOPIC_NAME, json_encode($eventData));
            } else {
                $this->logger->warning('Product data not found in wishlist item.');
            }
        }

        return $result;
    }
}