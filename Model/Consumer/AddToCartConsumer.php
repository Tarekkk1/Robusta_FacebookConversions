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

    public function processMessage( $data)
    {  
        $message = json_decode($data, true);
        $maskedCartId = $message['masked_cart_id'];
        $cartItems = $message['cart_items'];
        $eventTime = $message['event_time'];

        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepository->get($cartId);
        
        $eventsData = [];
        foreach ($cartItems as $cartItemData) {
            try {
                $sku = $cartItemData['sku'] ?? null;
                $qty = $cartItemData['quantity'] ?? 0;

                $cartItem = $cart->getItemByProductSku($sku);
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

            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        }
    }