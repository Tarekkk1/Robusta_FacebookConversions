<?php
namespace Robusta\FacebookConversions\Model\Consumer;

class CompleteRegistrationConsumer
{
    protected $conversionsAPI;
    protected $logger;

    public function __construct(
        \Robusta\FacebookConversions\Services\ConversionsAPI $conversionsAPI,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->conversionsAPI = $conversionsAPI;
        $this->logger = $logger;
    }

    public function processMessage( $message)
    {
        $eventData = json_decode($message, true);
        try {
            $this->conversionsAPI->sendEventToFacebook('CompleteRegistration', $eventData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}