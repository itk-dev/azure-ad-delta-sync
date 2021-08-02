<?php

namespace ItkDev\Adgangsstyring\Handler;

use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherHandler implements HandlerInterface
{
    private EventDispatcherInterface $eventDispatcher;

    /**
     * EventDispatcherHandler constructor.
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        $startEvent = new StartEvent();
        $this->eventDispatcher->dispatch($startEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function retainUsers(array $users): void
    {
        $event = new UserDataEvent($users);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $commitEvent = new CommitEvent();
        $this->eventDispatcher->dispatch($commitEvent);
    }
}
