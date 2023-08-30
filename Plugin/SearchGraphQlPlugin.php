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
             //print 
             
        try {
            if (isset($args['search']) && $args['search']) {
                $searchQuery = $args['search'];
                $this->searchObserver->sendSearchEventToFacebook($searchQuery);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}