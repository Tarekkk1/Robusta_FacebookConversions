<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class AddToCartObserver implements ObserverInterface
{
    protected $customerSession;
    protected $curl;
    protected $logger;

    public function __construct(
        Session $customerSession,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $request = $observer->getEvent()->getRequest();
        $qty = $request->getParam('qty');
        $customerEmail = '';
        if ($this->customerSession->isLoggedIn()) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
        }

        $this->sendAddToCartEventToFacebook($product, $qty, $customerEmail);
    }

    public function sendAddToCartEventToFacebook($product, $qty, $customerEmail)
    {
        
        $this->logger->info('AddToCart event in progress...');
        
        $pixelId = 'YOUR_PIXEL_ID'; 
        $accessToken = 'YOUR_ACCESS_TOKEN';

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

        $endpoint = "https://graph.facebook.com/v13.0/{$pixelId}/events?access_token={$accessToken}";

        try {
            $this->curl->post($endpoint, json_encode($data));
            $response = $this->curl->getBody();
            $this->logger->info('Successfully sent AddToCart event to Facebook: ' . $response);

        } catch (\Exception $e) {
            $this->logger->error('Error while sending data to Facebook: ' . $e->getMessage());
        }
    }
}