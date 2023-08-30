<?php
namespace Robusta\FacebookConversions\Observer;

use Magento\Framework\HTTP\Client\Curl;
use Magento\TestFramework\Utility\ChildrenClassesSearch\F;
use Psr\Log\LoggerInterface;
use Robusta\FacebookConversions\Services\CAPI as FBHelper;


class SearchObserver
{
    protected $curl;
    protected $logger;
    protected $FBHelper;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        FBHelper $FBHelper
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->FBHelper = $FBHelper;
    }

    public function sendSearchEventToFacebook($searchQuery)
    {
        $this->logger->info('Search event in progress...');

       
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
        $this->FBHelper->sendEventToFacebook('Search', $data);
    }
}