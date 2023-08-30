<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Customer;
use Psr\Log\LoggerInterface;

class CompleteRegistrationObserver implements ObserverInterface
{
    protected $logger;
    protected $curl;
    public function __construct(
        LoggerInterface $logger
        ,
        \Magento\Framework\HTTP\Client\Curl $curl
            
    ) {
        $this->logger = $logger;
        $this->curl = $curl;
    }

    public function execute(Observer $observer)
    {$this->logger->info('Complete registration observer triggered');

        /** @var Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        $customerEmail = $customer->getEmail();


        $this->sendCompleteRegistrationEventToFacebook($customerEmail);
    }

    public function sendCompleteRegistrationEventToFacebook($customerEmail)
{
    $this->logger->info('Complete registration event in progress...');

    $pixelId = 'YOUR_PIXEL_ID'; 
    $accessToken = 'YOUR_ACCESS_TOKEN';

    $data = [
        'data' => [
            [
                'event_name' => 'CompleteRegistration',
                'event_time' => time(),
                'user' => [
                    'email' => hash('sha256', $customerEmail)  
                ],
                'custom_data' => [
                    'status' => 'Completed'
                ],
            ],
        ],
    ]; 

    $endpoint = "https://graph.facebook.com/v13.0/{$pixelId}/events?access_token={$accessToken}";

    try {
        $this->curl->post($endpoint, json_encode($data));
        $response = $this->curl->getBody();
        $this->logger->info('Successfully sent CompleteRegistration event to Facebook: ' . $response);
    } catch (\Exception $e) {
        $this->logger->error('Error while sending CompleteRegistration data to Facebook: ' . $e->getMessage());
    }
}

}