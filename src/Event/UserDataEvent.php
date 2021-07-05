<?php


namespace ItkDev\Adgangsstyring\Event;


use Symfony\Contracts\EventDispatcher\Event;

class UserDataEvent extends Event
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
    public function setData(array $data)
    {
        $this->data = $data;
    }
}