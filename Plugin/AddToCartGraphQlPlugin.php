<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Services\ConversionsAPI;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class AddToCartGraphQlPlugin
{
    protected $logger;
    protected $conversionsAPI;
    protected $storeManager;
    protected $maskedQuoteIdToQuoteId;
    protected $cartRepository; 
    

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConversionsAPI $conversionsAPI,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository 
    ) {
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
        $this->storeManager = $storeManager;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository; 
    }

    public function afterResolve($subject, $result, $field, $context, $info, $value, $args)
    {
        $this->logger->info('working well in afterResolve');
        
        $maskedCartId = $args['cartId'] ?? null;
        $cartItemsInput = $args['cartItems'] ?? [];
        
        if (!is_string($maskedCartId)) {
            $this->logger->error('Expected maskedCartId to be a string but got: ' . gettype($maskedCartId));
            return $result;  // exit early if the type is wrong
        }
    
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepository->get($cartId);
        
        $eventsData = [];
        foreach ($cartItemsInput as $cartItemData) {
            try {
                $sku = $cartItemData['sku'] ?? null;
                $qty = $cartItemData['quantity'] ?? 0;
    
                if (!$sku) {
                    $this->logger->warning('SKU not found in cart items data.');
                    continue;
                }
                
                // Retrieve cart item by its SKU
                $cartItems = $cart->getAllItems();
                $matchedItem = null;
                foreach ($cartItems as $item) {
                    if ($item->getProduct()->getSku() == $sku) {
                        $matchedItem = $item;
                        break;
                    }
                }
                
                if (!$matchedItem) {
                    $this->logger->warning('Cart item not found for SKU: ' . $sku);
                    continue;
                }
                $this->logger->info('cart item found for SKU: ' . $sku);
                $cartItem = $matchedItem;
    
                $customerEmail = $cart->getCustomer()->getEmail();
                $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
    
                $eventData = [
                    'event_name' => 'AddToCart',
                    'event_time' => time(),
                    'user' => [
                        'email' => hash('sha256', $customerEmail),
                    ],
                    'custom_data' => [
                        'content_name' => $cartItem->getName(),
                        'content_id' => $cartItem->getSku(),
                        'quantity' => $qty,
                        'value' => $cartItem->getPrice(),
                        'currency' => $currencyCode,
                    ],
                ];
                
                $eventsData[] = $eventData;
    
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    
        if (!empty($eventsData)) {
            $this->conversionsAPI->sendEventToFacebook('AddToCart', ['data' => $eventsData]);
        }
    
        return $result;
    }
    

}