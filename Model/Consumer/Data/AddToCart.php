<?php
namespace Robusta\FacebookConversions\Model\Data;

use Robusta\FacebookConversions\Api\Data\AddToCartInterface;

class AddToCart implements AddToCartInterface
{
    private $eventsData;

    public function getEventsData(): array
    {
        return $this->eventsData;
    }

    public function setEventsData(array $eventsData): void
    {
        $this->eventsData = $eventsData;
    }
}