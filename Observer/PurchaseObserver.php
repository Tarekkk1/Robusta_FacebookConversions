<?php

namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use Robusta\FacebookConversions\Services\ConversionsAPI as ConversionsAPI;

class PurchaseObserver implements ObserverInterface
{
    protected $customerSession;
    protected $logger;
    protected $conversionsAPI;

    public function __construct(
        Session $customerSession,
        LoggerInterface $logger,
        ConversionsAPI $conversionsAPI
    ) {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $customerEmail = $order->getCustomerEmail();
        $total = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();
        $items = $order->getAllVisibleItems();
        $contents = [];
        $contentIds = [];
        foreach ($items as $item) {
            $contents[] = [
                'id' => (string)$item->getProductId(),
                'quantity' => $item->getQtyOrdered(),
                'item_price' => $item->getPrice()
            ];
            $contentIds[] = (string)$item->getProductId();
        }

        $this->sendPurchaseEventToFacebook($total, $customerEmail, $currency, $contents, $contentIds);
    }

    public function sendPurchaseEventToFacebook($total, $customerEmail, $currency, $contents, $contentIds)
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
                        'currency' => $currency,
                        'value' => $total,
                        'contents' => $contents,
                        'content_ids' => $contentIds,
                        'content_type' => 'product',
                        'num_items' => count($contents)
                    ],
                ],
            ],
        ];

        $this->conversionsAPI->sendEventToFacebook('Purchase', $data);
    }
}