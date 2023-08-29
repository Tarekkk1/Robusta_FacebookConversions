<?php
namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SearchObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $queryText = $observer->getEvent()->getQueryText();
    }
}