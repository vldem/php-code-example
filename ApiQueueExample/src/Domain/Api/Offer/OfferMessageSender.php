<?php

namespace App\Domain\Api\Offer;

use App\Queue\Dispatcher\MessageDispatcherInterface;
use App\Domain\Offer\OfferLoadMessage;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Domain.
 */
final class OfferMessageSender
{
    private MessageDispatcherInterface $messageDispatcher;

    /**
     * The constructor.
     *
     * @param MessageDispatcherInterface $messageDispatcher The message dispatcher
     *
     */
    public function __construct( MessageDispatcherInterface $messageDispatcher )
    {
        $this->messageDispatcher = $messageDispatcher;
    }

    /**
     * Send message to the queue.
     *
     * @param string $brokerId The broker id
     *
     * @return OfferLoadMessage object.
     */
    public function send( string $brokerId ): OfferLoadMessage
    {
        // set offer load message
        $result = $this->messageDispatcher->dispatch( new OfferLoadMessage( $brokerId ) );
        return $result;
    }
}