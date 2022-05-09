<?php

namespace App\Queue\Dispatcher;

use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use App\Domain\Offer\OfferLoadMessage;
use App\Domain\Postback\PostbackMessage;

final class RabbitMessageDispatcher implements MessageDispatcherInterface
{
    /**
     * @var AMQPChannel
     */
    private AMQPChannel $channel;

    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Dispatches the given message.
     *
     * @param object $event The message
     *
     * @throws JsonException
     * @return object The message
     */
    public function dispatch(object $event)
    {
        $properties = [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        $body = [
            'class_name' => get_class($event),
            'payload' => $event,
        ];

        $message = new AMQPMessage(json_encode($body, JSON_THROW_ON_ERROR), $properties);

        // Map class type to exchange and routing key
        if ($event instanceof OfferLoadMessage) {
            $exchange = '';
            $routingKey = 'offer_load';
        } elseif ($event instanceof PostbackMessage) {
            $exchange = '';
            $routingKey = 'postback';
        } else {
        // Default
            $exchange = '';
            $routingKey = 'events';
        }

        // Push to queue
        $this->channel->basic_publish($message, $exchange, $routingKey);

        return $event;
    }
}
