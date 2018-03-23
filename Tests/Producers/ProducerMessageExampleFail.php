<?php

namespace Qubit\Bundle\QubitMqBundle\Tests\Producers;

use Qubit\Bundle\QubitMqBundle\Events\Message;

class ProducerMessageExampleFail extends Message
{
    // Al no tener especificado un component y un action el servicio publish debería fallar
    protected $component = '';
    protected $action = '';
}
