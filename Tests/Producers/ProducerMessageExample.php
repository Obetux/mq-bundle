<?php

namespace Qubit\Bundle\QubitMqBundle\Tests\Producers;

use Qubit\Bundle\QubitMqBundle\Events\Message;

class ProducerMessageExample extends Message
{
    protected $component = 'login';
    protected $action = 'user_login';
}
