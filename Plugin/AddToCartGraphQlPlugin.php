<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class AddToCartGraphQlPlugin
{
    protected $logger;
    protected $publisher;
    protected $storeManager;
    protected $maskedQuoteIdToQuoteId;
    protected $cartRepository;
    const TOPIC_NAME = 'facebookconversions.addtocart';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PublisherInterface $publisher,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository 
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
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
            $this->publisher->publish(self::TOPIC_NAME, $eventsData);
        }

        return $result;
    }
}