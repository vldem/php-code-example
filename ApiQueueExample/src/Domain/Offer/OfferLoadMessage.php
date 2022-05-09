<?php

namespace App\Domain\Offer;
/**
 * DTO with public property for the payload
 */
final class OfferLoadMessage
{
    /**
     * @var string
     */
    public string $brokerId;

    /**
     * The constuctor
     */
    public function __construct(string $brokerId)
    {
        $this->brokerId = $brokerId;
    }
}
