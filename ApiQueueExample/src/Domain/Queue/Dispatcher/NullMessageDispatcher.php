<?php

namespace App\Queue\Dispatcher;
/**
 * Null dispatcher for unit test.
 * It does not send real message.
 */
final class NullMessageDispatcher implements MessageDispatcherInterface
{
    /**
     * Dispatches the given message.
     *
     * @param object $event The message
     *
     * @return object The message
     */
    public function dispatch(object $event)
    {
        return $event;
    }
}