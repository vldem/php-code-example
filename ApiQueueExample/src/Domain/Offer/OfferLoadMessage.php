<?php

namespace App\Domain\Offer;

final class OfferLoadMessage
{
    public string $brokerId;

    public function __construct(string $brokerId)
    {
        $this->brokerId = $brokerId;
    }
}