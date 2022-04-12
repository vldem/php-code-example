<?php

namespace App\Domain\Api\Rtb;

use App\Factory\DbConnection;
use App\Domain\Service\Helpper;
use App\Domain\Api\Rtb\RtbRequestTemplate;
use App\Domain\Api\Rtb\RtbMessage;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Domain.
 */
final class AdsRequester
{
    private RtbRequestTemplate $rtbRequestTemplate;
    private RtbMessage $rtbMessage;
    private DbConnection $dbConnection;
    private Helpper $helpper;
    private LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param RtbRequestTemplate $RtbRequestTemplate The RTB request template
     * @param RtbMessage $rtbMessage The RTB messages
     * @param DbConnection $dbConnection The Database connection
     * @param Helpper $helper The helpper
     * @param LoggerFactory $loggerFactory The logger
     */
    public function __construct(
        RtbRequestTemplate $rtbRequestTemplate,
        RtbMessage $rtbMessage,
        DbConnection $dbConnection,
        Helpper $helpper,
        LoggerFactory $loggerFactory
    )
    {
        $this->rtbRequestTemplate = $rtbRequestTemplate;
        $this->rtbMessage = $rtbMessage;
        $this->dbConnection = $dbConnection;
        $this->helpper = $helpper;
        $this->logger = $loggerFactory
            ->addFileHandler('api_rtb.log')
            ->createLogger();
    }

    /**
     * Rerquest ads code from RTB Exchange.
     *
     * @param string $brokerId The broker id
     * @param int $width The width of requested ads
     * @param int $height The height of requested ads
     * @param float $bidfloor The proposed bidfloor in currency format x.xx  of requested ads
     * @param string $mode The mode: test or normal if parametr is empty
     *
     * @return string with either error message or ads code to display to a user in browser.
     */
    public function requestAds( string $brokerId, int $width, int $height, int $bidfloor, string $mode = '' ): string
    {
        // set request parameters
        $idBin = openssl_random_pseudo_bytes(8);
        $id = bin2hex($idBin);

        $ua = $_SERVER['HTTP_USER_AGENT'];

        $ip = $this->helpper->getServerRemoteAddr();

        $country = $this->helpper->getCountryCodeByIP($ip);

        $cmd = $this->rtbRequestTemplate->getRequestTemplate(
            $brokerId,
            array( "{id}", "{witdh}", "{height}", "{ip}", "{ua}", "{country}", "{bidfloor}"),
            array( $id, $width, $height, $ip, $ua, $country, $bidfloor )
        );

        if ($mode == 'test') {
            $this->logger->info($cmd);
        }

        if ( empty($cmd) ) {
            $result = $this->rtbMessage->getMessage('requestEmpty');
        } else {
            list( $apiHeader, $apiResults) = $this->helpper->curlCmd(
                $this->rtbRequestTemplate->getApiURL( $brokerId ),
                array( $cmd ),
                array('Content-Type: application/json'),
                'POST'
            );
            if ($mode == 'test') {
                $this->logger->info( "response: " , array($apiHeader, $apiResults) );
            }

            $result = $this->processResponse( $apiHeader, $apiResults, $brokerId, $cmd );

        }
        return $result;
    }


    /**
     * Process response from RTB Exchange
     * @param string $apiHeader The response's header
     * @param string $apiResult The response's body
     * @param string $brokerId The broker id
     * @param string $cmd The XML request's body
     *
     * @return string RTB code if success or error message if failed
     */
    private function processResponse( string $apiHeader, string $apiResults, string $brokerId, string $cmd ): string
    {
        $statValue = '';
        if ( preg_match("/HTTP\/1.1 204/i", $apiHeader) ) {
            // no content
            $statValue = '204';
            $result = $this->rtbMessage->getMessage('apiNoBid') . " 204";
        } elseif ( !preg_match("/HTTP\/1.1 200 OK/i", $apiHeader) ) {
            //error during request
            if ( $apiHeader == '') {
               $respcode = 'request timeout';
            } else {
               $respcode = substr($apiHeader,0,20);
            }
            $statValue = $respcode;
            
            $result = $this->rtbMessage->getMessage('apiWrongStatus') . " header: $apiHeader";
            
        } else {
            // there is response from RTB Exchange
            $apiResults = json_decode($apiResults);

            if ($apiResults->nbr <> '') {
                //bid did not win, save the reason in DB
                $statValue = 'nbr:'. $apiResults->nbr;
                $result = $this->rtbMessage->getMessage('apiNoBid') . ' nbr->'. $apiResults->nbr;
                
            } else {
                // bid won, prepare data for ads code
                $price = $apiResults->seatbid[0]->bid[0]->price;
                $nurl = $apiResults->seatbid[0]->bid[0]->nurl;
                $adm = $apiResults->seatbid[0]->bid[0]->adm;
                $nurl=str_replace('${AUCTION_PRICE}',  $price , $nurl);
                $adm=str_replace('${AUCTION_PRICE}',  $price , $adm);

                if ($adm <> '') {
                    // return ads code
                    $statValue = '200';
                    $result = $adm;
                    
                } else {
                    // something is wrong, adm is empty
                    $statValue = 'adm is empty';
                    $result = $this->rtbMessage->getMessage('apiNoBid');
                    
                }
            }
        }
        // store statistic in DB 
        $res = $this->dbConnection->queryBind(
            $this->rtbRequestTemplate->getQuery('addRecToRtbStat'),
            'sss',
            array( $brokerId, $cmd , $statValue )
        );

        return $result;

    }


}
