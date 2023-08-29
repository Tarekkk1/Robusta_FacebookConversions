<?php
namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CompleteRegistrationObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
    }
}