<?php

namespace Qubit\Bundle\QubitMqBundle\Producers;

use Qubit\Bundle\QubitMqBundle\Events\Message;
use Qubit\Bundle\UtilsBundle\Generator\TrackingCode;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * ProducerEvent
 * @package Qubit\Bundle\QubitMqBundle\Producers\ProducerEvent
 */
class ProducerEvent
{
    private $rabbitProducer;
    private $module;
    private $logger;

    /**
     * __construct
     *
     * @param string $module Nombre del modulo levantado por configuración
     * @param Producer $rabbitProducerServiceEvent
     * @param Logger $logger
     */
    public function __construct($module, Producer $rabbitProducerServiceEvent, Logger $logger)
    {
        $this->module = $module;
        $this->rabbitProducer = $rabbitProducerServiceEvent;
        $this->logger = $logger;
    }

    /**
     * @param string $module
     * @return ProducerEvent
     */
    public function setModule(string $module): ProducerEvent
    {
        $this->module = $module;
        return $this;
    }


    /**
     * publish
     *
     * @param Message $message Objeto de tipo Message
     *
     * @return boolean
     */
    public function publish(Message $message)
    {
        $trackingCode = new TrackingCode();
        
        $message->setModule($this->module);
        $message->setTracking($trackingCode->getTrackingCode());
        
        if ($message->doValidations()) {
            try {
                $this->rabbitProducer->publish(json_encode($message->serialize()), $message->getRoutingKey());
                return true;
            } catch (\Exception $ex) {
                // Meto LOG de que hubo un fallo en la conección
                $this->logger->error('Hubo un error en intento de publish a rabbitMQ', [
                    'respuesta' => $ex->getMessage(),
                    'params' => json_encode($message->serialize())
                ]);
                return false;
            }
        } else {
            // Meto LOG de que hubo un error en las validaciones
            $this->logger->error('Hubo un error en validaciones de publish a rabbitMQ', [
                'params' => json_encode($message->serialize())
            ]);
            return false;
        }
    }
}
