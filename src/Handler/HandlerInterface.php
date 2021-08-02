<?php

namespace ItkDev\Adgangsstyring\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface HandlerInterface
{
    /**
     *
     */
    public function start(): void;

    /**
     * @param array $users
     */
    public function retainUsers(array $users): void;

    /**
     *
     */
    public function commit(): void;
}
