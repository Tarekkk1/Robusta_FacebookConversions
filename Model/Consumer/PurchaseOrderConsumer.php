<?php
namespace Robusta\FacebookConversions\Model\Consumer;

use Robusta\FacebookConversions\Services\ConversionsAPI;

class PurchaseOrderConsumer
{
    protected $conversionsAPI;
    protected $orderRepository;

    public function __construct(
        ConversionsAPI $conversionsAPI,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->orderRepository = $orderRepository;
    }

    public function processMessage($message)
    {
        $data = json_decode($message, true);
        $orderId = $data['order_id'];
        $order = $this->orderRepository->get($orderId);

        $itemsData = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $itemsData[] = [
                'sku' => $item->getSku(),
                'qty_ordered' => $item->getQtyOrdered(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'category_name' => $item->getCategory() ? $item->getCategory()->getName() : 'Default'
            ];
        }

        $data = [
            'customerEmail' => $order->getCustomerEmail(),
            'total' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'items' => $itemsData,
            'event_time' => $data['event_time'],
        ];

        $this->sendPurchaseEventToFacebook($data);
    }

    public function sendPurchaseEventToFacebook($data)
    {
        $eventData = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => $data['event_time'],
                    'user' => [
                        'email' => hash('sha256', $data['customerEmail'])
                    ],
                    'custom_data' => [
                        'currency' => $data['currency'],
                        'value' => $data['total'],
                        'contents' => $data['items'],
                        'content_ids' => array_column($data['items'], 'sku'),
                        'content_type' => 'product', 
                        'num_items' => count($data['items'])
                    ],
                ],
            ],
        ];

        $this->conversionsAPI->sendEventToFacebook('Purchase', $eventData);
    }
}