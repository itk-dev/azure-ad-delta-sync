<?php

namespace ItkDev\Adgangsstyring\Handler;

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
