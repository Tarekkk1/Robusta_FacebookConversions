<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Observer\AddToWishlistObserver;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddToWishlistGraphQlPlugin
{
    protected $addToWishlistObserver;
    protected $logger;
    protected $productRepository;

    public function __construct(
        AddToWishlistObserver $addToWishlistObserver,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository
    ) {
        $this->addToWishlistObserver = $addToWishlistObserver;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function afterExecute($subject, $result, $wishlist, $wishlistItems)
    {
        try {
            if (isset($wishlistItems['data']) && is_array($wishlistItems['data'])) {
                $data = $wishlistItems['data'];
                $sku = isset($data['sku']) ? $data['sku'] : null;

                if ($sku) {
                    $product = $this->productRepository->get($sku);
                    if ($product) {
                        $customerEmail = ''; 
                        if ($wishlist->getCustomer() && $wishlist->getCustomer()->getId()) {
                            $customerEmail = $wishlist->getCustomer()->getEmail();
                        }
                        $this->addToWishlistObserver->sendAddToWishlistEventToFacebook($product, $customerEmail);
                    } else {
                        $this->logger->warning('Product with SKU ' . $sku . ' not found.');
                    }
                } else {
                    $this->logger->warning('SKU not found in wishlist items data.');
                }
            } else {
                $this->logger->warning('Unexpected wishlist items format.');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}