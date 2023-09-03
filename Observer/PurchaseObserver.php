<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Magento\TestFramework\Utility\ChildrenClassesSearch\F;
use Psr\Log\LoggerInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI as ConversionsAPI;


class PurchaseObserver implements ObserverInterface
{
    protected $customerSession;
    protected $curl;
    protected $logger;
    protected $conversionsAPI;


    public function __construct(
        Session $customerSession,
        Curl $curl,
        LoggerInterface $logger,
        ConversionsAPI $conversionsAPI
        
    ) {
        $this->customerSession = $customerSession;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
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
        
        $this->conversionsAPI->sendEventToFacebook('Purchase', $data);
    }    
}