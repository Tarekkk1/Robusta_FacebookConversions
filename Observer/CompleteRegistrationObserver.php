<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Customer;
use Psr\Log\LoggerInterface;
use Robusta\FacebookConversions\Services\CAPI as FBHelper;

class CompleteRegistrationObserver implements ObserverInterface
{
    protected $logger;
    protected $curl;
    protected $FBHelper;
    public function __construct(
        LoggerInterface $logger
        ,
        \Magento\Framework\HTTP\Client\Curl $curl,
        FBHelper $FBHelper
            
    ) {
        $this->logger = $logger;
        $this->curl = $curl;
        $this->FBHelper = $FBHelper;
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

   $this->FBHelper->sendEventToFacebook('CompleteRegistration', $data);
}

}