<?php
namespace Robusta\FacebookConversions\Model\Consumer;

use Robusta\FacebookConversions\Services\ConversionsAPI;
use Psr\Log\LoggerInterface;

class SearchConsumer
{
    protected $conversionsAPI;
    protected $logger;

    public function __construct(
        ConversionsAPI $conversionsAPI,
        LoggerInterface $logger
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
    }

    public function processMessage($message)
    {
        $data = json_decode($message, true);
        try {
            $this->conversionsAPI->sendEventToFacebook('Search', $data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}