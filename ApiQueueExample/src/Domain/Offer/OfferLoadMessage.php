<?php

namespace App\Domain\Offer;
/**
 * DTO with public property for the payload
 */
final class OfferLoadMessage
{
    public string $brokerId;

    public function __construct(string $brokerId)
    {
        $this->brokerId = $brokerId;
    }
}
