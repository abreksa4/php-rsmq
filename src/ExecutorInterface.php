<?php declare(strict_types=1);


namespace AndrewBreksa\RSMQ;

/**
 * Interface ExecutorInterface
 *
 * @package AndrewBreksa\RSMQ
 * @author  Andrew Breksa <andrew@andrewbreksa.com>
 */
interface ExecutorInterface
{

    /**
     * Handle the message, retuning true will "ack" the message, false will not ack (causing the message to become
     * visible as per the queue's vt setting)
     *
     * @param  Message $message
     * @return bool
     */
    public function __invoke(Message $message): bool;
}
