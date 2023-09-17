<?php
namespace Robusta\FacebookConversions\Plugin;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Wishlist\Model\Wishlist;

class AddToWishlistGraphQlPlugin
{
    protected $logger;
    protected $publisher;
    const TOPIC_NAME = 'robusta.facebook.addtowishlist';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    public function afterResolve($subject, $result, $field, $context, $info, $value, $args)
    {
        $productId = $args['product_id'] ?? null;
    
            $eventData = [
                'event_time' => time(),
                'wishlist_id' =>$result['id'],  
                'product_id' => $productId
            ];
          
            $this->publisher->publish(self::TOPIC_NAME, json_encode($eventData));
        
    
        return $result;
    }
    
}