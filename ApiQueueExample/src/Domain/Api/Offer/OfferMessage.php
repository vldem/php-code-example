<?php

namespace App\Domain\Api\Offer;

use App\Domain\Service\Helpper;

/**
 * Domain.
 */
final class OfferMessage
{
    private Helpper $helpper;

    private $messages = [
        'successOfferLoadMessage' => "Offer loading request has been successfuly sent to queue for brokerId {brokerId}.",
    ];

    /**
     * The constructor.
     *
     * @param Helpper $helper The helpper
     */
    public function __construct( Helpper $helpper )
    {
        $this->helpper = $helpper;
    }

    /**
     * Get message string
     * @param string $name The message name
     * @param array $params (optional) list of parameter's tags to substitude with values
     * @param array $values (optional) list of parameter's values to substitude in tags
     * @return string messager string
     */
    public function getMessage( string $name, $params=array(), $values=array() ): string
    {
        $msg =  $this->messages[$name];
        return  $this->helpper->setValuesToParams( $msg, $params, $values );
    }

}