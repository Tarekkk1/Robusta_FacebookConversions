<?php

namespace Robusta\FacebookConversions\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI;

class AddToCartGraphQlPlugin
{
    protected $addToCartObserver;
    protected $logger;
    protected $productRepository;
    protected $conversionsAPI;  

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        ConversionsAPI $conversionsAPI   
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->conversionsAPI = $conversionsAPI;  
    }

    public function afterExecute($subject, $result, $cart, $cartItems)
    {
        if (!isset($cartItems['data']) || !is_array($cartItems['data'])) {
            $this->logger->warning('Unexpected cart items format.');
            return $result;
        }

        $data = $cartItems['data'];
        $sku = isset($data['sku']) ? $data['sku'] : null;
        $qty = isset($data['quantity']) ? $data['quantity'] : 0;

        if (!$sku) {
            $this->logger->warning('SKU not found in cart items data.');
            return $result;
        }

        $product = $this->productRepository->get($sku);

        if (!$product) {
            $this->logger->warning('Product with SKU ' . $sku . ' not found.');
            return $result;
        }

        $customerEmail = '';

        if ($cart->getCustomer() && $cart->getCustomer()->getId()) {
            $customerEmail = $cart->getCustomer()->getEmail();
        }
        
        $this->logger->info('AddToCart event in progress...');

        try{  
            $data = [
                'data' => [
                    [
                        'event_name' => 'AddToCart',
                        'event_time' => time(),
                        'user' => [
                            'email' => hash('sha256', $customerEmail)
                        ],
                        'custom_data' => [
                            'product_name' => $product->getName(),
                            'product_id' => $product->getId(),
                            'quantity' => $qty,
                            'price' => $product->getFinalPrice(),
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