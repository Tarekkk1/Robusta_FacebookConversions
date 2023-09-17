<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Framework\MessageQueue\PublisherInterface;

class AddToCartGraphQlPlugin
{
    protected $logger;
    protected $publisher;
    const TOPIC_NAME = 'robusta.facebook.addtocart';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    public function afterResolve($subject, $result, $field, $context, $info, $value, $args)
    {
        $maskedCartId = $args['cartId'] ?? null;
        $cartItems = $args['cartItems'] ?? [];
        $this->logger->info('AddToCartGraphQlPlugin: ' . $maskedCartId);

        try {
            $data = [
                'event_time' => time(),
                'masked_cart_id' => $maskedCartId,
                'cart_items' => $cartItems,
            ];

            $this->publisher->publish(self::TOPIC_NAME, json_encode($data));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

}