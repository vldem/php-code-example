<?php

namespace App\Domain\Companies;

use App\Domain\Delivery\Data\DeliveryCalcResult;

interface CompanyInterface
{
    public function CalcDelivery( string $sourceKladr, string $targetKladr, float $weight): DeliveryCalcResult;
}