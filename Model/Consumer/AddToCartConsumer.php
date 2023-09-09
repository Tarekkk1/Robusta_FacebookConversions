<?php
namespace Robusta\FacebookConversions\Model\Consumer;

use Robusta\FacebookConversions\Services\ConversionsAPI;

class AddToCartConsumer
{
    protected $conversionsAPI;
    protected $logger;

    public function __construct(
        ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
    }

    public function processMessage(array $message)
    {
        try {
            $this->conversionsAPI->sendEventToFacebook('AddToCart', ['data' => $message]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send event to Facebook: ' . $e->getMessage());
        }
    }
}