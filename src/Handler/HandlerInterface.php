<?php

namespace ItkDev\Adgangsstyring\Handler;

interface HandlerInterface
{
    /**
     * Collect users and create deletion list.
     */
    public function collectUsersForDeletionList(): void;

    /**
     * Remove users provided from the deletion list.
     *
     * @param array $users
     */
    public function removeUsersFromDeletionList(array $users): void;

    /**
     * Handle user deletion list, as no more users will be removed.
     */
    public function commitDeletionList(): void;
}
