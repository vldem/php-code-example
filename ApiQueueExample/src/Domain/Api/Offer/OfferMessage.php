<?php

namespace App\Domain\Api\Offer;

use App\Domain\Service\Helper;

/**
 * Domain.
 */
final class OfferMessage
{
    private Helper $helper;

    private $messages = [
        'successOfferLoadMessage' => "Offer loading request has been successfuly sent to queue for brokerId {brokerId}.",
    ];

    /**
     * The constructor.
     *
     * @param Helper $helper The helper
     */
    public function __construct( Helper $helper )
    {
        $this->helper = $helper;
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
        return  $this->helper->setValuesToParams( $msg, $params, $values );
    }

}
