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
        $this->logger->info('working well in afterResolve');
        $maskedCartId = $args['cartId'] ?? null;
        $cartItems = $args['cartItems'] ?? [];

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


 // public function afterResolve($subject, $result, $field, $context, $info, $value, $args)
    // {
    //     $this->logger->info('working well in afterResolve');
        
    //     $maskedCartId = $args['cartId'] ?? null;
    //     $cartItemsInput = $args['cartItems'] ?? [];
        
    //     if (!is_string($maskedCartId)) {
    //         $this->logger->error('Expected maskedCartId to be a string but got: ' . gettype($maskedCartId));
    //         return $result;  
    //     }
    
    //     $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
    //     $cart = $this->cartRepository->get($cartId);
        
    //     $eventsData = [];
    //     foreach ($cartItemsInput as $cartItemData) {
    //         try {
    //             $sku = $cartItemData['sku'] ?? null;
    //             $qty = $cartItemData['quantity'] ?? 0;
    
    //             if (!$sku) {
    //                 $this->logger->warning('SKU not found in cart items data.');
    //                 continue;
    //             }
                
    //             $cartItems = $cart->getAllItems();
    //             $matchedItem = null;
    //             foreach ($cartItems as $item) {
    //                 if ($item->getProduct()->getSku() == $sku) {
    //                     $matchedItem = $item;
    //                     break;
    //                 }
    //             }
                
    //             if (!$matchedItem) {
    //                 $this->logger->warning('Cart item not found for SKU: ' . $sku);
    //                 continue;
    //             }
    //             $this->logger->info('cart item found for SKU: ' . $sku);
    //             $cartItem = $matchedItem;
    
    //             $customerEmail = $cart->getCustomer()->getEmail();
    //             $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
    
    //             $eventData = [
    //                 'event_name' => 'AddToCart',
    //                 'event_time' => time(),
    //                 'user' => [
    //                     'email' => hash('sha256', $customerEmail),
    //                 ],
    //                 'custom_data' => [
    //                     'content_name' => $cartItem->getName(),
    //                     'content_id' => $cartItem->getSku(),
    //                     'quantity' => $qty,
    //                     'value' => $cartItem->getPrice(),
    //                     'currency' => $currencyCode,
    //                 ],
    //             ];
                
    //             $eventsData[] = $eventData;
    
    //         } catch (\Exception $e) {
    //             $this->logger->error($e->getMessage());
    //         }
    //     }
    
    //     if (!empty($eventsData)) {
    //         $this->conversionsAPI->sendEventToFacebook('AddToCart', ['data' => $eventsData]);
    //     }
    
    //     return $result;
    // }