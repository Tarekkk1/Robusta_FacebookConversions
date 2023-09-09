<?php
namespace Robusta\FacebookConversions\Model\Consumer;

use Robusta\FacebookConversions\Services\ConversionsAPI;

class PurchaseOrderConsumer
{
    protected $conversionsAPI;

    public function __construct(ConversionsAPI $conversionsAPI)
    {
        $this->conversionsAPI = $conversionsAPI;
    }

    public function processMessage($data)
    {
        $total = $data['total'];
        $customerEmail = $data['customerEmail'];
        $currency = $data['currency'];
        $items = $data['items'];
        $contents = [];
        $contentIds = [];
        foreach ($items as $item) {
            $contents[] = [
                'id' => $item->getSku(),
                'quantity' => $item->getQtyOrdered(),
                'content_name' => $item->getName(),
                'item_price' => $item->getPrice(),
                'content_category' => $item->getCategory() ? $item->getCategory()->getName() : 'Default',
            ];
            $contentIds[] = $item->getSku();
        }

        $this->sendPurchaseEventToFacebook($total, $customerEmail, $currency, $contents, $contentIds);
    }

    public function sendPurchaseEventToFacebook($total, $customerEmail, $currency, $contents, $contentIds)
    {
        $eventData = [
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

        $this->conversionsAPI->sendEventToFacebook('Purchase', $eventData);
    }
}