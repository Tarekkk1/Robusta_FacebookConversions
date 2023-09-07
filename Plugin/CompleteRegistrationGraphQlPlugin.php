<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Services\ConversionsAPI;
use Magento\Store\Model\StoreManagerInterface;

class CompleteRegistrationGraphQlPlugin
{
    protected $logger;
    protected $conversionsAPI;
    protected $storeManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConversionsAPI $conversionsAPI,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
        $this->storeManager = $storeManager;
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
        
            $this->conversionsAPI->sendEventToFacebook('CompleteRegistration', $eventData);
        } 
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}