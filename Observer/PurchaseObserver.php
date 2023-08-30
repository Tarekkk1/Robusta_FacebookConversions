<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class PurchaseObserver implements ObserverInterface
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
        $order = $observer->getEvent()->getOrder();
        $customerEmail = $order->getCustomerEmail();
        $total = $order->getGrandTotal();

        $this->sendPurchaseEventToFacebook($total, $customerEmail);
    }

    public function sendPurchaseEventToFacebook($total, $customerEmail)
    {
        $this->logger->info('Purchase event in progress...');
        
        $pixelId = 'YOUR_PIXEL_ID'; 
        $accessToken = 'YOUR_ACCESS_TOKEN';

        $data = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => time(),
                    'user' => [
                        'email' => hash('sha256', $customerEmail)
                    ],
                    'custom_data' => [
                        'currency' => 'USD',  
                          'value' => $total
                    ],
                ],
            ],
        ]; 

        $endpoint = "https://graph.facebook.com/v13.0/{$pixelId}/events?access_token={$accessToken}";

        try {
            $this->curl->post($endpoint, json_encode($data));
            $response = $this->curl->getBody();
            $this->logger->info('Successfully sent Purchase event to Facebook: ' . $response);

        } catch (\Exception $e) {
            $this->logger->error('Error while sending data to Facebook: ' . $e->getMessage());
        }
    }
}