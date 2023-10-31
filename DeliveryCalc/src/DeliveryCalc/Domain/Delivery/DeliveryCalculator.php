<?php

namespace App\Domain\Delivery;

use App\Domain\Companies\CompanyInterface;
use App\Domain\Delivery\Data\DeliveryCalcResult;

final class DeliveryCalculator
{
    /**
     * @var CompanyInterface $company
     */
    private CompanyInterface $company;

    /**
     * The constructor.
     *
     * @param CompanyInterface $company - delivery company
     */
    public function __construct(CompanyInterface $company)
    {
        $this->company = $company;
    }

    public function calculateDeliveryParams( string $sourceKladr, string $targetKladr, float $weight): DeliveryCalcResult
    {
        return $this->company->CalcDelivery($sourceKladr,  $targetKladr, $weight);
    }

}