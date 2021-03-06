<?php

namespace App\Domain\Api\Rtb;

use App\Domain\Service\Helpper;

/**
 * Domain
 */
final class RtbRequestTemplate
{
    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var array
     */
    protected $apiUrl = [
        'kru' => 'http://ads4.example.com?c=rtb&m=req&key=100500',
    ];

    /**
     * @var array
     */
    protected $requestTemplates = [
        'kru' => "
            {
                \"id\":\"{id}\",
                \"at\": 1,
                \"tmax\": 500,
                \"imp\":[
                    {\"id\":\"1\",
                    \"banner\":{\"w\":{witdh},\"h\":{height}},
                    \"instl\":0,
                    \"secure\":1,
                    \"bidfloor\":{bidfloor}}
                ],
                \"site\":{\"id\": \"1\",
                    \"name\": \"example\",
                    \"domain\": \"example.com\",
                    \"cat\": [\"IAB3-1\"],
                    \"privacypolicy\": 1,
                    \"mobile\": 0},
                \"device\":{\"ip\":\"{ip}\",
                    \"ua\":\"{ua}\",
                    \"js\":1,
                    \"geo\": {\"region\":\"{country}\"}
                },
                \"user\":{},
                \"test\": 0,
                \"cur\":[\"USD\"],
                \"ext\": {}
            }
        ",
    ];

    /**
     * @var array
     */
    protected $dbQueries = [
        'addRecToRtbStat' => "INSERT into rtbstat (d,bid,request,respcode) values (now(),?,?,?)",
    ];

    /**
     * The constructor.
     *
     * @param Helper $helper The helper
     */
    public function __construct(
        Helper $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * Get XML request template for needed broker
     * @param string $bid The broker id
     * @param array $params (optional) list of parameter's tags to substitute with values
     * @param array $values (optional) list of parameter's values to substitute in tags
     * @return string XML string of request
     */
    public function getRequestTemplate(string $bid, array $params = array(), array $values = array()): string
    {
        $xml = $this->requestTemplates[$bid];
        return  $this->helper->setValuesToParams( $xml, $params, $values );
    }


    /**
     * Get api request URL
     * @param string $bid The broker id
     * @param array $params (optional) list of parameter's tags to substitute with values
     * @param array $values (optional) list of parameter's values to substitute in tags
     * @return string URL string of request
     */
    public function getApiURL( string $bid, array $params = array(), array $values = array() ): string
    {
        $url = $this->apiUrl[$bid];
        return  $this->helper->setValuesToParams( $url, $params, $values );
    }

    /**
     * Get sql string for DB query
     * @param string $name The query name
     * @return string SQL string
     */
    public function getQuery( string $name ): string {
        return $this->dbQueries[$name];
    }


}
