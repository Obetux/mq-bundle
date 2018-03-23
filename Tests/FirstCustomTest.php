<?php

namespace Qubit\Bundle\QubitMqBundle\Tests;

use Qubit\Bundle\QubitMqBundle\Tests\Core\TestKernel;
use Qubit\Bundle\QubitMqBundle\Callbacks\GenericConsumerStrategy;
use Qubit\Bundle\QubitMqBundle\Producers\ProducerEvent;
use Qubit\Bundle\QubitMqBundle\Tests\Producers\ProducerMessageExample;
use Qubit\Bundle\QubitMqBundle\Tests\Producers\ProducerMessageExampleFail;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Bridge\Monolog\Logger;

class FirstCustomTest extends \PHPUnit\Framework\TestCase
{
    protected $container;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function loggerMock()
    {
        $logger = $this->createMock(Logger::class);

        $logger->method('debug')->willReturn(true);
        $logger->method('info')->willReturn(true);
        $logger->method('warning')->willReturn(true);
        $logger->method('error')->willReturn(true);

        return $logger;
    }

    /**
     * @return ProducerEvent
     */
    private function eventProducerMock()
    {
        //Mockeo el producer de OldSoundRabbit
        $producer = $this->createMock(Producer::class);
        $producer->method('publish')->willReturn(true);

        // Mockeo el logger
        $logger = $this->loggerMock();

        // Generamos copia exacta del producer pasandole el módulo, el mock del producer de rabbitMQ y el logger
        $producerEvent = new ProducerEvent('test', $producer, $logger);

        return $producerEvent;
    }

    private function genericConsumerStrategyService()
    {
        // Mockeo el logger
        $logger = $this->loggerMock();

        $genericConsumerStrategy = new GenericConsumerStrategy($logger);

        return $genericConsumerStrategy;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        require_once __DIR__ . '/Core/TestKernel.php';
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $this->container = $kernel->getContainer();
    }
    
    /**
     * testProducer
     */
    public function testProducer()
    {
        // Probamos que este el servicio del producer generado
//        $eventProducer = $this->container->get('qubit.event.producer');
        $eventProducer = $this->eventProducerMock();
        
        $this->assertEquals(
            ProducerEvent::class,
            get_class($eventProducer),
            'ContainerBuilder class assert instanceof ProducerEvent class'
        );
    }
    
    /**
     * testProducerSuccess
     */
    public function testProducerSuccess()
    {
        $message = new ProducerMessageExample();
        $message->setTags(array('login'));
        $message->setPayload(array(
            'username' => 'qa.prueba@gmail.com',
            'userId' => '3',
            'device' => 'web',
            'model' => 'firefox',
            'agent' => 'Mozilla/5.0 (Platform; Security; OS-or-CPU; Localization; rv:1.0.2) '
            . 'Gecko/20030208 Netscape/7.02'
        ));
        
        //Mockeo el producer de OldSoundRabbit
        $producer = $this->createMock(Producer::class);
        $producer->method('publish')->willReturn(true);

        // Mockeo el logger
        $logger = $this->createMock(Logger::class);
        $logger->method('debug')->willReturn(true);
        $logger->method('info')->willReturn(true);
        $logger->method('warning')->willReturn(true);
        $logger->method('error')->willReturn(true);

        // Generamos copia exacta del producer pasandole el módulo, el mock del producer de rabbitMQ y el logger
        $producerEvent = new ProducerEvent('test', $producer, $logger);
        
        // Probamos enviar el mensaje al producer
        $result = $producerEvent->publish($message);
        $this->assertTrue(
            $result == true,
            'Fallo en publish, o no paso las validaciones o hubo un problema al intentar publicar mensaje a rabbitMQ'
        );
    }
    
    /**
     * testProducerFail
     */
    public function testProducerFail()
    {
        $message = new ProducerMessageExampleFail();
        $message->setTags(array('login'));
        $message->setPayload(array(
            'username' => 'qa.prueba@gmail.com',
            'userId' => '3',
            'device' => 'web',
            'model' => 'firefox',
            'agent' => 'Mozilla/5.0 (Platform; Security; OS-or-CPU; Localization; rv:1.0.2) '
            . 'Gecko/20030208 Netscape/7.02'
        ));
        
        // Obtenemos el producer
//        $eventProducer = $this->container->get('qubit.event.producer');
        $eventProducer = $this->eventProducerMock();

        // Probamos enviar el mensaje al producer esperando un false en el resultado
        $result = $eventProducer->publish($message);
        $this->assertTrue($result == false);
    }
    
    /**
     * testConsumer
     */
    public function testConsumer()
    {
        // Chequeamos que este el servicio de rabbitMQ
//        $genericConsumerStrategy = $this->container->get('qubit.generic.consumer.strategy');
        $genericConsumerStrategy = $this->genericConsumerStrategyService();

        $this->assertEquals(
            GenericConsumerStrategy::class,
            get_class($genericConsumerStrategy),
            'ContainerBuilder class assert instanceof TrackingCodeProcessor class'
        );
    }
    
    /**
     * testConsumerSuccess
     */
    public function testConsumerSuccess()
    {
        // Mockeamos un mensaje de tipo AMQPMessage que es devuelto por el rabbitMQ Bundle
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
                ->setMethods(array('get'))
                ->setConstructorArgs(array(
                    '{"time":{"date":"2017-07-19 17:36:42.000000","timezone_type":3,'
                    . '"timezone":"America\/Argentina\/Buenos_Aires"},"tracking":"dc888061","entry_type":"EVENT",'
                    . '"loglevel":"INFO","module":"test","component":"login","action":"user_login","tags":["login"],'
                    . '"payload":{"username":"qa.prueba@gmail.com","userId":"3","device":"web","model":"firefox",'
                    . '"agent":"Mozilla\/5.0 (Platform; Security; OS-or-CPU; Localization; rv:1.0.2) '
                    . 'Gecko\/20030208 Netscape\/7.02"}}'
                ))
                ->getMock();
        
        // Obtengo el consumer generic
//        $genericConsumerStrategy = $this->container->get('qubit.generic.consumer.strategy');
        $genericConsumerStrategy = $this->genericConsumerStrategyService();

        $response = $genericConsumerStrategy->execute($message);
        
        $this->assertEquals(true, $response);
    }
    
    /**
     * testConsumerFail
     */
    public function testConsumerFail()
    {
        // Mockeamos un mensaje de tipo AMQPMessage que es devuelto por el rabbitMQ Bundle
        $message = $this->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
                ->setMethods(array('get'))
                ->setConstructorArgs(array(
                    '{"time":{"date":"2017-07-19 17:36:42.000000","timezone_type":3,'
                    . '"timezone":"America\/Argentina\/Buenos_Aires"},"tracking":"dc888061","entry_type":"EVENT",'
                    . '"loglevel":"INFO","module":"test","component":"login","action":"user_logout","tags":["login"],'
                    . '"payload":{"username":"qa.prueba@gmail.com","userId":"3","device":"web","model":"firefox",'
                    . '"agent":"Mozilla\/5.0 (Platform; Security; OS-or-CPU; Localization; rv:1.0.2) '
                    . 'Gecko\/20030208 Netscape\/7.02"}}'
                ))
                ->getMock();
        
        // Obtengo el consumer generic
//        $genericConsumerStrategy = $this->container->get('qubit.generic.consumer.strategy');
        $genericConsumerStrategy = $this->genericConsumerStrategyService();

        $response = $genericConsumerStrategy->execute($message);
        
        $this->assertEquals(true, $response);
    }
    
    /**
     * testMessageFormat
     */
    public function testMessageFormat()
    {
        $message = new ProducerMessageExampleFail();
        $message->setTags(array('login'));
        $message->setPayload(array(
            'username' => 'qa.prueba@gmail.com',
            'userId' => '3',
            'device' => 'web',
            'model' => 'firefox',
            'agent' => 'Mozilla/5.0 (Platform; Security; OS-or-CPU; Localization; rv:1.0.2) '
            . 'Gecko/20030208 Netscape/7.02'
        ));
        
        // Serializo message
        $serialize = $message->serialize();
        
        // Chequeo que payload sea array
        $this->assertTrue(is_array($serialize['payload']));
        // Chequeo contener el elemento time
        $this->assertArrayHasKey('time', $serialize);
        // Chequeo que el elemento time sea string
        $this->assertInternalType('string', $serialize['time']);

        // Deserealizo message
        $message->deserialize($serialize);
        
        // Chequeo que el payload sea stdObject
        $this->assertTrue(is_object($message->getPayload()));
    }
}
