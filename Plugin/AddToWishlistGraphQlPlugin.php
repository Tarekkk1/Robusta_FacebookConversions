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
        $wishlist = $value['model'];
        $this->logger->info('WishlistGraphqlPlugin: ' . $wishlist->getId());

        if ($wishlist instanceof Wishlist) {
            $eventData = [
                'event_time' => time(),
                'wishlist_id' => $wishlist->getId()
            ];
            $this->publisher->publish(self::TOPIC_NAME, json_encode($eventData));
        }

        return $result;
    }
}