<?php
namespace Robusta\FacebookConversions\Model\Consumer;

class AddToWishlistConsumer
{
    protected $conversionsAPI;
    protected $logger;

    public function __construct(
        Robusta\FacebookConversions\Services\ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
    }

    public function processMessage(array $eventData)
    {
        try {
            $this->conversionsAPI->sendEventToFacebook('AddToWishlist', $eventData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}