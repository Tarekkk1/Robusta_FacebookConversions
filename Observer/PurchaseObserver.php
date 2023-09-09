<?php
namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class PurchaseObserver implements ObserverInterface
{
    protected $customerSession;
    protected $logger;
    protected $publisher;

    public function __construct(
        Session $customerSession,
        LoggerInterface $logger,
        PublisherInterface $publisher
    ) {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            $this->logger->error('Order not found.');
            return;
        }

        $data = [
            'order_id' => $order->getId(),
            'event_time' => time(),
        ];
        $this->publisher->publish('facebookconversions.purchaseorder', json_encode($data));
    }
}