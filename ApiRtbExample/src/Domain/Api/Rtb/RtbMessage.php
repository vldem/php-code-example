<?php

namespace App\Domain\Api\Rtb;

use App\Domain\Service\Helpper;

/**
 * Service.
 */
final class RtbMessage
{
    private Helpper $helpper;

    private $messages = [
        'apiWrongStatus' => "no bid. Respond status is not correct. See the respond header for detailed information.",
        'apiNoBid' => "no bid",
        'requestEmpty' => "no bid. Request string is empty."
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
     * @param string $name The message's index name
     * @param array $params (optional) list of parameter's tags to substitute with values
     * @param array $values (optional) list of parameter's values to substitute in tags
     * @return string messager string
     */
    public function getMessage( string $name, $params=array(), $values=array() ): string
    {
        $msg =  $this->messages[$name];
        return  $this->helpper->setValuesToParams( $msg, $params, $values );
    }

}
