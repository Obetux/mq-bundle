<?php

namespace Qubit\Bundle\QubitMqBundle\Tests\Services;

use Qubit\Bundle\QubitMqBundle\Events\Message;

class SuccessServiceExample
{
    public function execute(Message $msg)
    {
        return true;
    }
}
