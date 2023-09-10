<?php
namespace Robusta\FacebookConversions\Api\Data;

interface AddToCartInterface
{
    public function getEventsData(): array;

    public function setEventsData(array $eventsData): void;
}