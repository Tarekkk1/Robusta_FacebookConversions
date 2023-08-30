<?php
namespace Robusta\FacebookConversions\Plugin;

use Robusta\FacebookConversions\Observer\SearchObserver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\ErrorLog\Logger;

class SearchGraphQlPlugin
{
    protected $searchObserver;
    protected $logger;

    public function __construct(
        SearchObserver $searchObserver,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->searchObserver = $searchObserver;
        $this->logger = $logger;
    }

    public function afterResolve($subject, $result, $args)
    {
        $searchQuery = null;
    
        // Check if $args is an object and has a 'search' property.
        if (is_object($args) && isset($args->search)) {
            $searchQuery = $args->search;
        } 
        elseif (is_array($args) && isset($args['search'])) {
            $searchQuery = $args['search'];
        }
    
        if ($searchQuery) {
            $this->logger->info('Search event in progress...');
            try {
                $this->searchObserver->sendSearchEventToFacebook($searchQuery);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    
        return $result;
    }
    
    
}