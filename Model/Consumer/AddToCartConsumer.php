<?php

namespace Robusta\FacebookConversions\Model\Consumer;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Store\Model\StoreManagerInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI;

class AddToCartConsumer
{
    protected $conversionsAPI;
    protected $logger;
    protected $storeManager;
    protected $maskedQuoteIdToQuoteId;
    protected $cartRepository;

    public function __construct(
        ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
    }

    public function processMessage($data)
    {
        $this->logger->info('Processing AddToCart event...');
        try {
            $message = json_decode($data, true);
            $this->logger->info('AddToCart message: ' . json_encode($message));

            if (!is_array($message) || !isset($message['masked_cart_id'], $message['cart_items'], $message['event_time'])) {
                throw new \Exception('Invalid message format');
            }

            $maskedCartId = $message['masked_cart_id'];
            $cartItems = $message['cart_items'];
            $eventTime = $message['event_time'];

            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
            $cart = $this->cartRepository->get($cartId);

            foreach ($cart->getAllItems() as $item) {
                $this->logger->info('Item SKU in cart: ' . $item->getSku());
            }

            $eventsData = [];
            foreach ($cartItems as $cartItemData) {
                $sku = $cartItemData['sku'] ?? null;
                $qty = $cartItemData['quantity'] ?? 0;

                $cartItem = null;
                foreach ($cart->getAllItems() as $item) {
                    if ($item->getSku() == $sku) {
                        $cartItem = $item;
                        break;
                    }
                }

                if (!$cartItem) {
                    $this->logger->error("Product with SKU {$sku} not found in cart.");
                    continue;
                }

                $customerEmail = $cart->getCustomer()->getEmail();
                $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

                $eventData = [
                    'event_name' => 'AddToCart',
                    'event_time' => $eventTime,
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
                $this->logger->info('AddToCart event data: ' . json_encode($eventData));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        if (!empty($eventsData)) {
            $this->logger->info('Sending AddToCart event to Facebook...');
            $this->conversionsAPI->sendEventToFacebook('AddToCart', ['data' => $eventsData]);
        }
    }

    
}