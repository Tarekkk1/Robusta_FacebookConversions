<?php

namespace Robusta\FacebookConversions\Services;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class CAPI
{
    protected $curl;
    protected $logger;

    const PIXEL_ID = 'YOUR_PIXEL_ID'; 
    const ACCESS_TOKEN = 'YOUR_ACCESS_TOKEN';
    const ENDPOINT_BASE = "https://graph.facebook.com/v13.0/";

    public function __construct(
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function sendEventToFacebook($eventName, $data)
    {
       

        $endpoint = self::ENDPOINT_BASE . self::PIXEL_ID . "/events?access_token=" . self::ACCESS_TOKEN;

        try {
            $this->curl->post($endpoint, json_encode($data));
            $response = $this->curl->getBody();
            $this->logger->info('Successfully sent ' . $eventName . ' event to Facebook: ' . $response);
        } catch (\Exception $e) {
            $this->logger->error('Error while sending data to Facebook: ' . $e->getMessage());
        }
    }
}