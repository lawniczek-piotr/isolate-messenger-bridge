<?php

namespace Isolate\Tests;

class DummyMessage
{
    /**
     * @var string
     */
    private $message;

    /**
     * DummyMessage constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
