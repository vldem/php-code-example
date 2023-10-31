<?php

namespace App\Domain\Delivery\Data;

final class DeliveryCalcResult
{
    /**
     * @var float $price
     */
    public float $price;

    /**
     * @var string $date
     */
    public string $date;

    /**
     * @var string error;
     */
    public string $error;

    public function __construct(float $price, string $date, string $error )
    {
        $this->price = $price;
        $this->date = $date;
        $this->error = $error;
    }

}