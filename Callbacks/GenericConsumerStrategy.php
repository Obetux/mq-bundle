<?php

namespace Qubit\Bundle\QubitMqBundle\Callbacks;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Qubit\Bundle\QubitMqBundle\Events\Message;
use Monolog\Logger;

/**
 * GenericConsumerStrategy
 * @package Qubit\Bundle\QubitMqBundle\Callbacks\GenericConsumerStrategy
 */
class GenericConsumerStrategy implements ConsumerInterface
{
    private $routingKeys = [];
    private $indexKeys = [];
    private $logger;
    
    /**
     * __construct
     *
     * @param object $logger Objeto de tipo Container
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * execute
     *
     * @param object $msg AMQPMessage object
     *
     * @return mixed
     */
    public function execute(AMQPMessage $msg)
    {
        $message = new Message();
        $message->deserialize(get_object_vars(json_decode($msg->body)));

        $routingKey = $message->getRoutingKey();
        
        $this->indexKeys = array_keys($this->routingKeys);
        
        if (!in_array($routingKey, $this->indexKeys)) {
            $explotedKey = explode('.', $routingKey);
            if (!$service = $this->checkRoutingKey($explotedKey)) {
                // Si no encontro por patrón *, busco por patron #
                if (!$service = $this->checkRoutingKey($explotedKey, '#')) {
                    // No se encontraron routing keys asociadas, genero log y mando a borrar de la lista en rabbit
                    $this->logger->warning('No se encontró routing key asociada', [
                        'routingKey' => $message->getRoutingKey(),
                        'params' => json_encode($message->serialize())
                    ]);
                    return true;
                }
            }
        } else {
            $service = $this->routingKeys[$routingKey];
        }
        // Si llego aca, genero llamada del servicio correspondiente al routing key
        return $service->execute($message);
    }
    
    /**
     * checkRoutingKey
     *
     * @param array $explotedKey Array con los elementos del routing key explotados por punto (.)
     * @param string $pattern Patrón a buscar de las cadenas
     *
     * @return mixed
     */
    private function checkRoutingKey($explotedKey, $pattern = '*')
    {
        $position = count($explotedKey);
        
        for ($i = $position; $i >= 0; $i--) {
            if (isset($explotedKey[$i])) {
                $newKey = $explotedKey;
                $newKey[$i] = $pattern;
                
                $implodedKey = implode('.', $newKey);
                
                if (in_array($implodedKey, $this->indexKeys)) {
                    // Si encuentro el routing key, devuelvo el servicio
                    return $this->routingKeys[$implodedKey];
                }
                
                if ($pattern == '#') {
                    array_pop($explotedKey);
                }
            }
        }
        return false;
    }
    
    /**
     * setReoutingKeyService
     *
     * @param array $handler Array con nombre del routing key y nombre del servicio
     */
    public function setReoutingKeyService(array $handler)
    {
        $this->routingKeys[$handler['name']] = $handler['service'];
    }
}
