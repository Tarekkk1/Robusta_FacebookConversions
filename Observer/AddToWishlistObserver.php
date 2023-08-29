<?php
namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddToWishlistObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
    }
}