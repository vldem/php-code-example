<?php

namespace App\Domain\Services;

final class CurlRequester
{

    /**
     * @var self
     */
    private static $instance;

    /**
     * Return self
     *
     * @return self
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Constructor closed
     */
    private function __construct()
    {
    }

    /**
     * Clone forbidden
     */
    private function __clone()
    {
    }

    /**
     * Serialization forbidden
     */
    private function __sleep()
    {
    }

    /**
     * Deserialization forbidden
     */
    private function __wakeup()
    {
    }

    /**
     * This is an emulator of curl request to delivery company's api
     * It generates random answer from delivery company.
     *
     * CURL request handler
     *
     * @param string $url The URL to call
     * @param string $cmd The request type like GET, POST, etc
     * @param $postdata (optional) The POST data
     * @param $headers  (optional) The headers data
     *
     * @return string with response's body.
     *
     */
    public function curlCmd( string $url, string $cmd='GET', $postdata = '', $qheaders = ''): string
    {
        $result = array();

        switch ($url) {
            case 'http://fast_delivery.ru':
                $result['price'] = round(rand(1,10000) / 10, 2);
                $result['period'] = rand(1, 10);
                $result['error'] = "";
                $isError = rand(1, 10);
                if ($isError % 4 == 0) {
                    $result['error'] = "error";
                    $result['price'] = 0.00;
                    $result['period'] = 0;
                }
                break;
            case 'http://slow_delivery.ru':
                $result['coefficient'] = round(rand(10,1000) / 100, 2);
                $result['date'] = date('Y-m-d', time() + rand(1, 10) * 86400);
                $result['error'] = "";
                $isError = rand(1, 20);
                if ($isError % 4 == 0) {
                    $result['error'] = "error";
                    $result['date'] = '';
                    $result['coefficient'] = 0.0;
                }
                break;

        }

        return json_encode($result);
    }


}