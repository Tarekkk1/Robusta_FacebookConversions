<?php

namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Services\ConversionsAPI;

class SearchGraphQlPlugin
{
    protected $logger;
    protected $conversionsAPI;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConversionsAPI $conversionsAPI
    ) {
        $this->logger = $logger;
        $this->conversionsAPI = $conversionsAPI;
    }

    public function afterResolve($subject, $result, $args)
    {
        if (!is_array($args) || !isset($args['search'])) {
            return $result;
        }

        $searchQuery = $args['search'];

        if (!$searchQuery) {
            return $result;
        }

        $this->logger->info('Search event in progress...');

        try {
            $eventData = [
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

            $this->conversionsAPI->sendEventToFacebook('Search', $eventData);

        } 
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}