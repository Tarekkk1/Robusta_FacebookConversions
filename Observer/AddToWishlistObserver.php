<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Robusta\FacebookConversions\Services\CAPI as FBHelper;


class AddToWishlistObserver implements ObserverInterface
{
    protected $customerSession;
    protected $curl;
    protected $logger;
    protected $FBHelper;

    public function __construct(
        Session $customerSession,
        Curl $curl,
        LoggerInterface $logger,
        FBHelper $FBHelper
    ) {
        $this->customerSession = $customerSession;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->FBHelper = $FBHelper;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $customerEmail = '';
        
        if ($this->customerSession->isLoggedIn()) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
        }

        $this->sendAddToWishlistEventToFacebook($product, $customerEmail);
    }

    public function sendAddToWishlistEventToFacebook($product, $customerEmail)
    {
        $this->logger->info('AddToWishlist event in progress...');
        
    
        $data = [
            'data' => [
                [
                    'event_name' => 'AddToWishlist',
                    'event_time' => time(),
                    'user' => [
                        'email' => hash('sha256', $customerEmail)
                    ],
                    'custom_data' => [
                        'product_name' => $product->getName(),
                        'product_id' => $product->getId(),
                        'price' => $product->getFinalPrice(),
                    ],
                ],
            ],
        ]; 

        $this->FBHelper->sendEventToFacebook('AddToWishlist', $data);
      
    }
}