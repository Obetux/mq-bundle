<?php

namespace Qubit\Bundle\QubitMqBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class QubitMqExtension extends Extension implements PrependExtensionInterface
{
    const CONSUMER_NAME = 'events';
    
    private $requiredBundles = ['UtilsBundle', 'LogBundle', 'OldSoundRabbitMqBundle'];
    
    public function prepend(ContainerBuilder $container)
    {
        // Obtenemos la configuración específica del qubit_rabbit_bundle bundle
        $qubitRabbitConfiguration = $container->getExtensionConfig('qubit_mq');
        // Obtenemos los bundles cargados por el kernel
        $bundles = $container->getParameter('kernel.bundles');
        
        $this->checkRequiredBundles($bundles);
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $qubitRabbitConfiguration);
        
        // Generamos el servicio que va a ser usado como callback general de los consumers
        $callbackDefinition = new Definition('Qubit\Bundle\QubitMqBundle\Callbacks\GenericConsumerStrategy');
        $callbackDefinition->addTag('qubit.generic.consumer.strategy');
        $callbackDefinition->addArgument(new Reference('monolog.logger.qubit_rabbit_event'));
        $container->setDefinition('qubit.generic.consumer.strategy', $callbackDefinition);
        
        // Seteamos los CONSUMERS de la configuración
        $consumers['consumers'] = array();
        if (isset($config['consumers'])) {
            foreach ($config['consumers'] as $index => $consumerConfig) {
                $this->injectRoutingKeys($config['consumers'][$index], $callbackDefinition, $container);
                
                $consumers['consumers'][$index] = [
                    'connection' => 'default',
                    'queue_options' => [
                        'name' => $consumerConfig['name'],
                        'durable' => true,
//                            'arguments' => ['x-message-ttl' => ['I', 3600000]]
                    ],
                    'callback' => 'qubit.generic.consumer.strategy'
                ];
            }
        }
        
        foreach ($container->getExtensions() as $name => $extension) {
            switch ($name) {
                // Agregamos a la configuración del rabbitMQ, los producers y consumers seteados anteriormente
                case 'old_sound_rabbit_mq':
                    // Obtengo la conección (staging|prod)
                    $connection = $this->getConnection($config['sandbox']);
                    
                    $rabbitMqConfig = [
                        'connections' => $connection,
                        'producers' => ['qubit_event' => [
                            'connection' => 'default',
                            'exchange_options' => ['name' => self::CONSUMER_NAME, 'type' => 'topic'],
                            'service_alias' => 'my_app_service'
                            ]
                        ],
                        'consumers' => $consumers['consumers']
                    ];
                    
                    $container->prependExtensionConfig($name, $rabbitMqConfig);
                    break;
                case 'monolog':
                    // Seteo el monolog para tener un canal solo para los eventos de rabbitMQ
                    $monologConfig['channels'][] = 'qubit_rabbit_event';
                    $monologConfig['handlers']['qubit_rabbit_event_handler'] = [
                        'type' => 'rotating_file',
                        'path' => '%kernel.logs_dir%/qubit_rabbit_event_%kernel.environment%.log',
                        'level' => 'debug',
                        'channels' => 'qubit_rabbit_event',
                        'max_files' => 5,
                        'formatter' => 'qubit.line.formatter',
                    ];

                    $container->prependExtensionConfig($name, $monologConfig);
                    break;
            }
        }
    }
    
    /**
     * getConnection
     *
     * @param bool $sandbox Boleano levantado de la configuración
     * @return array
     */
    private function getConnection($sandbox)
    {
        $connection = [
            'default' => [
                'host' => 'rabbitmq.qubit.tv',
                'port' => 5672,
                'user' => 'admin',
                'password' => 'k0rn4l1t0$',
                'vhost' => '/',
                'lazy' => false,
                'connection_timeout' => 3,
                'read_write_timeout' => 3,
                'keepalive' => false,
                'heartbeat' => 0
            ]
        ];
        
        if ($sandbox) {
            // Si es sandbox le seteo el host de staging
            $connection['default']['host'] = 'rabbitmq-staging.qubit.tv';
        }
        return $connection;
    }
    
    /**
     * injectRoutingKeys
     *
     * @param array  $service            Array con los datos de los handlers de un consumer
     * @param object $callbackDefinition Objeto Definition del serivico callback genérico
     * @param object $container          Objeto de tipo ContainerBuilder
     */
    private function injectRoutingKeys($service, $callbackDefinition, ContainerBuilder $container)
    {
        if (!empty($service['handler'])) {
            foreach ($service['handler'] as $handler) {
                // Injectamos el routingKey con la definición del servicio especificado
                $handler['service'] = $container->getDefinition($handler['service']);
                $callbackDefinition->addMethodCall('setReoutingKeyService', [$handler]);
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
    }
    
    /**
     * checkRequiredBundles
     *
     * @param array $loadedBundles Array of bundles loaded by the kernel
     */
    private function checkRequiredBundles(array $loadedBundles)
    {
        // Chequeo que los bundles necesarios estén
        foreach ($this->requiredBundles as $bundle) {
            if (false === isset($loadedBundles[$bundle])) {
                throw new InvalidConfigurationException('QubitMqBundle require que agregues el bundle: ' . $bundle);
            }
        }
        
        $newArray = array_keys($loadedBundles);
        
        // Chequeo que primero este el RabbitMQ para evitar romper
        $OldSoundRabbitMqBundleKey = array_search('OldSoundRabbitMqBundle', $newArray);
        $QubitMqBundleBundle = array_search('QubitMqBundle', $newArray);
 
        if ($OldSoundRabbitMqBundleKey > $QubitMqBundleBundle) {
            throw new InvalidConfigurationException('OldSoundRabbitMqBundle debe estar antes '
                    . 'del QubitMqBundle en el AppKernel.php');
        }
    }
}
