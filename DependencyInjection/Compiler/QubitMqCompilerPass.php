<?php

namespace Qubit\Bundle\QubitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class QubitMqCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Obtenemos la definition del servicio de rabbitMQ
        $rabbitProducerService = $container->getDefinition('old_sound_rabbit_mq.qubit_event_producer');

        // Obtenemos el module al cual pertenece el producer seteado por configuración
        $configs = $container->getExtensionConfig('qubit_mq');

        // Generamos el servicio producer a exponer y le pasamos al construct el
        // módulo, el sevicio del rabbitMQ y el logger
        $eventProducerDefinition = new Definition('Qubit\Bundle\QubitMqBundle\Producers\ProducerEvent');
        $eventProducerDefinition->setPublic(true);
        $eventProducerDefinition->addTag('qubit.event.producer');
        $eventProducerDefinition->addArgument($configs[0]['producer']['module']);
        $eventProducerDefinition->addArgument($rabbitProducerService);
        $eventProducerDefinition->addArgument(new Reference('monolog.logger.qubit_rabbit_event'));

        $container->setDefinition('qubit.event.producer', $eventProducerDefinition);
    }
}
