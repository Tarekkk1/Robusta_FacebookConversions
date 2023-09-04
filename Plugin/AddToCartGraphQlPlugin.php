<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI;

class AddToCartGraphQlPlugin
{
    protected $logger;
    protected $productRepository;
    protected $storeManager;
    protected $conversionsAPI;  

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ConversionsAPI $conversionsAPI
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->conversionsAPI = $conversionsAPI;  
    }

    public function afterExecute($subject, $result, $cart, $cartItems)
    {
        if (!isset($cartItems['data']) || !is_array($cartItems['data'])) {
            $this->logger->warning('Unexpected cart items format.');
            return $result;
        }

        $data = $cartItems['data'];
        $sku = $data['sku'] ?? null;
        $qty = $data['quantity'] ?? 0;

        if (!$sku) {
            $this->logger->warning('SKU not found in cart items data.');
            return $result;
        }

        try {
            $product = $this->productRepository->get($sku);

            $customerEmail = '';
            if ($cart->getCustomer() && $cart->getCustomer()->getEmail()) {
                $customerEmail = $cart->getCustomer()->getEmail();
            }
        
            $this->logger->info('AddToCart event in progress...');

            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

            $data = [
                'data' => [
                    [
                        'event_name' => 'AddToCart',
                        'event_time' => time(),
                        'event_source_url' => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
                        'user' => [
                            'email' => hash('sha256', $customerEmail),
                        ],
                        'custom_data' => [
                            'content_name' => $product->getName(),
                            'content_id' => $product->getId(),
                            'quantity' => $qty,
                            'value' => $product->getFinalPrice(),
                            'currency' => $currencyCode,
                        ],
                    ],
                ],
            ];
            
            $this->conversionsAPI->sendEventToFacebook('AddToCart', $data);
        } 
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    
        return $result;
    }
} 