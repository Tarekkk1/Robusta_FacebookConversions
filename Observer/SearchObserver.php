<?php
namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class SearchObserver
{
    protected $curl;
    protected $logger;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function sendSearchEventToFacebook($searchQuery)
    {
        $this->logger->info('Search event in progress...');

        $pixelId = 'YOUR_PIXEL_ID'; 
        $accessToken = 'YOUR_ACCESS_TOKEN';

        $data = [
            'data' => [
                [
                    'event_name' => 'Search',
                    'event_time' => time(),
                    'custom_data' => [
                        'search_string' => $searchQuery,
                    ],
                ],
            ],
        ]; 

        $endpoint = "https://graph.facebook.com/v13.0/{$pixelId}/events?access_token={$accessToken}";

        try {
            $this->curl->post($endpoint, json_encode($data));
            $response = $this->curl->getBody();
            $this->logger->info('Successfully sent Search event to Facebook: ' . $response);
        } catch (\Exception $e) {
            $this->logger->error('Error while sending data to Facebook: ' . $e->getMessage());
        }
    }
}