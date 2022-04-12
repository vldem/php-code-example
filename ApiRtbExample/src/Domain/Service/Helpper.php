<?php

namespace App\Domain\Service;

use \GeoIp2\Database\Reader;

/**
 * Service.
 */
final class Helpper
{

    private $settings;

    /**
     * The constructor.
     *
     * @param array $settings The settings of helpper
     */
    public function __construct( array $settings )
    {
        $this->settings = $settings;
    }

    /**
     * get server's remote address helper
     * @return string ip address
     */
    public function getServerRemoteAddr(): string
    {
        $ip = '';
        if ( array_key_exists('HTTP_CF_CONNECTING_IP',$_SERVER) && $_SERVER['HTTP_CF_CONNECTING_IP'] <> '' ) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * get country code from ip address by geoip database
     * @param $ip The IP address
     *
     * @return string The country code for provided IP address
     */
    function getCountryCodeByIP( string $ip ): string
    {
        if ($dbpath == '') {
            return '';
        }

        if ($ip == '') {
            return '';
        }
        if($ip == '127.0.0.1'){
            return 'US';
        }

        $reader = new Reader( $this->settings['geoipDb'] );
        try{
            $record = $reader->country($ip);
        }catch(Exception $e){
            $cc = '';
        }
        $cc = $record->country->isoCode;
        return $cc;
    } #getCountryCodeByIP

    /**
     * Substitute values to parameters tags in the string.
     * @param string $str      The string where to substitude values
     * @param array  $tags     array of parameter tags (like '{var1}') that will be replaced by values array
     * @param array  $values   array of values that will replace parameter tags.
     * @return string          updated sql string.
     */
    public function setValuesToParams( $str, $tags = array(), $values = array() ): string
    {
        if ( isset($tags) and isset($values) ) {
            $countTags = count($tags);
            $countVal = count($values);
            if ( $countTags > 0 and $countVal > 0 and $countTags == $countVal ) {
                $str = str_replace($tags, $values, $str);
            }
        }
        return $str;
    }

    /**
     * CURL request handler
     * @param string $url The URL to call
     * @param $postdata (optional) The POST data
     * @param $headers  (optional) The headers data
     * @param $cmd The request type like GET, POST, etc
     */
    function curlCmd($url, $postdata = '', $qheaders = '', $cmd='GET' ): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $cmd);
        if ($qheaders<>'') {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$qheaders);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_VERBOSE, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($cmd == 'POST' or $cmd == 'PATCH' or $cmd == 'DELETE') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postdata <>'') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $postdata));
            }
        }
        $rr = curl_exec($ch);
        $headersize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($rr, 0, $headersize);
        curl_close($ch);
        $result = substr($rr, $headersize);
        return( array($headers,$result) );
    }

}
