<?php

namespace ItkDev\Adgangsstyring\Handler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherHandlerTest extends TestCase
{
  /**
   * Testing event dispatch.
   */
  public function testDispatch()
  {
    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $eventDispatcher
      ->expects($this->exactly(4))
      ->method('dispatch');

    $handler = new EventDispatcherHandler($eventDispatcher);
    $handler->start();
    $handler->retainUsers([]);
    $handler->retainUsers([]);
    $handler->commit();
  }
}
