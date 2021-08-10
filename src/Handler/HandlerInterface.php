<?php

namespace ItkDev\Adgangsstyring\Handler;

interface HandlerInterface
{
    /**
     *
     */
    public function collectUsersForDeletionList(): void;

    /**
     * @param array $users
     */
    public function removeUsersFromDeletionList(array $users): void;

    /**
     *
     */
    public function commitDeletionList(): void;
}
