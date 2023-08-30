<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Observer\CompleteRegistrationObserver;
use Magento\Customer\Api\Data\CustomerInterface;

class CreateCustomerGraphQlPlugin
{
    protected $completeRegistrationObserver;
    protected $logger;

    public function __construct(
        CompleteRegistrationObserver $completeRegistrationObserver,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->completeRegistrationObserver = $completeRegistrationObserver;
        $this->logger = $logger;
    }

    public function afterResolve($subject, $result)
    { $this->logger->info('Create Customer  event in progress...');

      
        try {

            if (is_array($result)) {
            
                if (isset($result['customer'])) {
                    $this->logger->info('Result contains customer data.');
                    $customer = $result['customer'];
                    if (isset($customer['email'])) {
                        $this->logger->info('Customer email: ' . $customer['email']);
                        $customerEmail = $customer['email'];
                        $this->completeRegistrationObserver->sendCompleteRegistrationEventToFacebook($customerEmail);
                    } else {
                        $this->logger->warning('Customer email not found in result.');
                    }
                } else {
                    $this->logger->warning('Customer data not found in result.');
                }
            } else {
                $this->logger->warning('Unexpected result format.');
            }
              
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}