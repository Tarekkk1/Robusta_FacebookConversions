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
    const TOPIC_NAME = 'robusta.facebook.purchase';

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
        $this->publisher->publish(  self::TOPIC_NAME, json_encode($data));
    }
}
// public function execute(Observer $observer)
// {
//     $order = $observer->getEvent()->getOrder();
//     $customerEmail = $order->getCustomerEmail();
//     $total = $order->getGrandTotal();
//     $currency = $order->getOrderCurrencyCode();
//     $items = $order->getAllVisibleItems();
//     $contents = [];
//     $contentIds = [];
//     foreach ($items as $item) {
//         $contents[] = [
//             'id' => $item->getSku(),
//             'quantity' => $item->getQtyOrdered(),
//             'content_name' => $item->getName(),
//             'item_price' => $item->getPrice(),
//             'content_category' => $item->getCategory() ? $item->getCategory()->getName() : 'Default',
//         ];
//         $contentIds[] = $item->getSku();
//     }

//     $this->sendPurchaseEventToFacebook($total, $customerEmail, $currency, $contents, $contentIds);
// }

// public function sendPurchaseEventToFacebook($total, $customerEmail, $currency, $contents, $contentIds)
// {
//     $this->logger->info('Purchase event in progress...');

//     $data = [
//         'data' => [
//             [
//                 'event_name' => 'Purchase',
//                 'event_time' => time(),
//                 'user' => [
//                     'email' => hash('sha256', $customerEmail)
//                 ],
//                 'custom_data' => [
//                     'currency' => $currency,
//                     'value' => $total,
//                     'contents' => $contents,
//                     'content_ids' => $contentIds,
//                     'content_type' => 'product', 
//                     'num_items' => count($contents)
//                 ],
//             ],
//         ],
//     ];

//     $this->conversionsAPI->sendEventToFacebook('Purchase', $data);
// }