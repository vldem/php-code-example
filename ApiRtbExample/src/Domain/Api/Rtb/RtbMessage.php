<?php

namespace App\Domain\Api\Rtb;

use App\Domain\Service\Helper;

/**
 * Service.
 */
final class RtbMessage
{
    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var array
     */
    private $messages = [
        'apiWrongStatus' => "no bid. Respond status is not correct. See the respond header for detailed information.",
        'apiNoBid' => "no bid",
        'requestEmpty' => "no bid. Request string is empty."
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
     * @param string $name The message's index name
     * @param array $params (optional) list of parameter's tags to substitute with values
     * @param array $values (optional) list of parameter's values to substitute in tags
     * @return string messager string
     */
    public function getMessage( string $name, $params=array(), $values=array() ): string
    {
        $msg =  $this->messages[$name];
        return  $this->helper->setValuesToParams( $msg, $params, $values );
    }

}
