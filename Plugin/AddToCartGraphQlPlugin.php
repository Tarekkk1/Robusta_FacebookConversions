<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Observer\AddToCartObserver;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddToCartGraphQlPlugin
{
    protected $addToCartObserver;
    protected $logger;
    protected $productRepository;

    public function __construct(
        AddToCartObserver $addToCartObserver,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository
    ) {
        $this->addToCartObserver = $addToCartObserver;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function afterExecute($subject, $result, $cart, $cartItems)
    {
       

        try {
            if (isset($cartItems['data']) && is_array($cartItems['data'])) {
                $data = $cartItems['data'];
                $sku = isset($data['sku']) ? $data['sku'] : null;
                $qty = isset($data['quantity']) ? $data['quantity'] : 0;

                if ($sku) {
                    $product = $this->productRepository->get($sku);
                    if ($product) {
                        $customerEmail = ''; 
                        if ($cart->getCustomer() && $cart->getCustomer()->getId()) {
                            $customerEmail = $cart->getCustomer()->getEmail();
                        }
                        $this->addToCartObserver->sendAddToCartEventToFacebook($product, $qty, $customerEmail);
                    } else {
                        $this->logger->warning('Product with SKU ' . $sku . ' not found.');
                    }
                } else {
                    $this->logger->warning('SKU not found in cart items data.');
                }
            } else {
                $this->logger->warning('Unexpected cart items format.');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}