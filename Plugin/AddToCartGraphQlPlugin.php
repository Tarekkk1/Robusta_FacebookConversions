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

    public function afterExecute($subject, $result, $maskedCartId, $cartItems)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepository->get($cartId);
        
        $eventsData = [];
        foreach ($cartItems as $cartItemData) {
            try {
                $sku = $cartItemData['sku'] ?? null;
                $qty = $cartItemData['quantity'] ?? 0;

                if (!$sku) {
                    $this->logger->warning('SKU not found in cart items data.');
                    continue;
                }
                
                $cartItem = $cart->getItemByProductSku($sku);
                if (!$cartItem) {
                    $this->logger->warning('Cart item not found for SKU: ' . $sku);
                    continue;
                }

                $customerEmail = '';
                $customerEmail = $cart->getCustomer()->getEmail();
               
                
                $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

                $eventData = [
                    'event_name' => 'AddToCart',
                    'event_time' => time(),
                    // 'event_source_url' => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
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