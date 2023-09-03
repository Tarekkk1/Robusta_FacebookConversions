<?php

namespace Robusta\FacebookConversions\Services;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConversionsAPI
{
    protected $curl;
    protected $logger;

    const CONFIG_PATH_PIXEL_ID = 'facebookconversions/general/pixel_id';
    const CONFIG_PATH_ACCESS_TOKEN = 'facebookconversions/general/access_token';
    
    protected $scopeConfig;
    
    const ENDPOINT_BASE = "https://graph.facebook.com/v13.0/";

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function sendEventToFacebook($eventName, $data)
    {
        $pixelId = $this->scopeConfig->getValue(self::CONFIG_PATH_PIXEL_ID, ScopeInterface::SCOPE_STORE);
        $accessToken = $this->scopeConfig->getValue(self::CONFIG_PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);

        if (!$pixelId || !$accessToken) {
            $this->logger->error('Pixel ID or Access Token is not set');
            return;
        }
        
        $this->logger->info('Sending ' . $eventName . ' event to Facebook...');
        $endpoint = self::ENDPOINT_BASE . $pixelId . "/events?access_token=" . $accessToken;

        try {
            $this->curl->post($endpoint, json_encode($data));
            $response = $this->curl->getBody();
            $this->logger->info('Successfully sent ' . $eventName . ' event to Facebook: ' . $response);
        } 
        catch (\Exception $e) {
            $this->logger->error('Error while sending data to Facebook: ' . $e->getMessage());
        }
    }
}