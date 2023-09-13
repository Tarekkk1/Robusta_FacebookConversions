<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Services\ConversionsAPI;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class CompleteRegistrationGraphQlPlugin
{
    protected $logger;
    protected $conversionsAPI;
    protected $storeManager;
    protected $publisher;
    const   TOPIC_NAME ='robusta.facebook.registration';
  
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConversionsAPI $conversionsAPI,
        StoreManagerInterface $storeManager,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
        $this->storeManager = $storeManager;
        $this->publisher = $publisher;
    }

    public function afterResolve($subject, $result)
    {
        if (!is_array($result) || !isset($result['customer'])) {
            $this->logger->warning('Unexpected result format or customer data not found.');
            return $result;
        }

        $customer = $result['customer'];
        
        if (!isset($customer['email'])) {
            $this->logger->warning('Customer email not found in result.');
            return $result;
        }
        
        try {
            $this->logger->info('Create Customer event in progress...');
            $this->logger->info('Customer email: ' . $customer['email']);
            
            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        
            $eventData = [
                'data' => [
                    [
                        'event_name' => 'CompleteRegistration',
                        'event_time' => time(),
                        'user' => [
                            'email' => hash('sha256', $customer['email'])
                        ],
                        'custom_data' => [
                            'content_name' => 'Registration',
                            'currency' => $currencyCode,
                            'status' => 'Completed',
                            'value' => 0,
                        ],
                    ],
                ],
            ];
            $this->publisher->publish(self::TOPIC_NAME, json_encode($eventData));
         } 
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}