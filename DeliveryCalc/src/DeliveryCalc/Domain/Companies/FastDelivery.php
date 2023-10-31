<?php
namespace App\Domain\Companies;

use App\Domain\Services\CurlRequester;
use App\Domain\Delivery\Data\DeliveryCalcResult;

final class FastDelivery implements CompanyInterface
{
    const BASE_URL = "http://fast_delivery.ru";

    /**
     * @var CurlRequester $curlRequester
     */
    private CurlRequester $curlRequester;

    /**
     * The constructor.
     *
     * @param CurlRequester $curlRequester - Curl requester
     */
    public function __construct(CurlRequester $curlRequester)
    {
        $this->curlRequester = $curlRequester;
    }

    /**
     * Calculate delivery price and delivery date by sending request to delivery company's api
     *
     * @var string $sourceKladr - source address
     * @var string $targetKladr - destination address
     * @var float $weight  -  weight of delivery
     *
     * @return DeliveryCalcResult object {
     *      float  $price - delivery price
     *      string $date  - delivery date
     *      string $error - error message
     * }
     */
    public function CalcDelivery( string $sourceKladr, string $targetKladr, float $weight): DeliveryCalcResult
    {
        $answer = $this->curlRequester->curlCmd(self::BASE_URL);
        $answer = json_decode($answer);

        if ( !is_object($answer) ) {
            return ( new DeliveryCalcResult(0.0, '', 'empty result') );
        }

        if ( !$this->validateResponse($answer)) {
            return ( new DeliveryCalcResult(0.0, '', 'response validation failed') );
        }

        if ($answer->error <> '') {
            return ( new DeliveryCalcResult(0.0, '', $answer->error) );
        }

        // Set needed timezone here
        date_default_timezone_set('UTC');

        $curTime = date("H:i:s", time());

        if ( $curTime > "18:00:00" ) {
            $answer->period++;
        }

        $date =  time() + 86400 * $answer->period;

        return ( new DeliveryCalcResult( $answer->price, date("Y-m-d", $date), $answer->error) );

    }

    /**
     * Validate response result
     *
     * @var array $response - array with response data
     *
     * @return bool  true - validation passed, false - validation failed
     */
    private function validateResponse(object $response): bool
    {
        if ( isset($response->price) and !is_float($response->price + 0.0) ) {
            return false;
        }
        if (isset($response->period) and
            !is_string($response->period) and
            !preg_match("/^[0-9]+$/",$response->period)
        ) {
            return false;
        }

        if (isset($response->error) and !is_string($response->error)) {
            return false;
        }

        return true;
    }
}