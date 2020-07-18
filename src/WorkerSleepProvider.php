<?php declare(strict_types=1);


namespace AndrewBreksa\RSMQ;

/**
 * Interface WorkerDelayProvider
 *
 * @package AndrewBreksa\RSMQ
 * @author  Andrew Breksa <andrew@andrewbreksa.com>
 */
interface WorkerSleepProvider
{

    /**
     * Return the number of seconds that the worker should sleep for before grabbing the next message.
     * Returning null will cause the worker to exit.
     *
     * Note: this method is called _before_ the receiveMessage method is called.
     *
     * @return int|null
     */
    public function getSleep(): ?int;
}
