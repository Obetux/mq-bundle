<?php

namespace Qubit\Bundle\QubitMqBundle\Tests\Services;

use Qubit\Bundle\QubitMqBundle\Events\Message;

class FailedServiceExample
{
    public function execute(Message $msg)
    {
        return false;
    }
}
