<p align="center">
    <a href="http://www.qubit.tv" target="_blank">
        <img width=500 height=200 src="https://stcloudfront.qubit.tv/assets/public/qubit/qubit-ar/prod/images/logo-qubit-azul.svg">
    </a>
</p>

[Qubit\QubitMqBundle][1] es un bundle que funciona de wrapper y agrega funcionalidad a [RabbitMqBundle][2] para
la produccion y consumo de mensajes utilizando [RabbitMQ][3]

Requerimientos
--------------
**No hace falta instalarlos porque ya lo requiere a traves de composer.json**

* [UtilsBundle][4]
* [LogBundle][5]
* [Monolog][5]
* [rabbitmq-bundle][2]


Instalación
-----------

* Editar composer para agregar el servidor SATIS de Qubit:

```json
...
"repositories": [
    {
        "type": "composer",
        "url": "https://repo-manager.qubit.tv/"
    },
],
...
```

* Requerimos el [Qubit\QubitMqBundle][1] dejando que composer elija la versión estable

```bash
$ composer require qubit/rabbit-bundle
```

* Agregar los bundles al AppKernel

```php
<?php
// app/AppKernel.php

use Symfony\Component\HttpKernel\Kernel;

...
class AppKernel extends Kernel
{
    ...
    
    public function registerBundles()
    {
        ...

        $bundles = [
            ...
            $bundles[] = new Qubit\Bundle\UtilsBundle\UtilsBundle();
            $bundles[] = new Qubit\Bundle\LogBundle\LogBundle();
            $bundles[] = new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle();
            $bundles[] = new Qubit\Bundle\QubitMqBundle\QubitMqBundle();
            ...
        ];

        ...
    }

    ...
}
```

Configuración
-------------

En la misma se deberá especificar un consumer y los producers que el usuario necesite.

```yaml
# app/config/config_dev.yml|config_prod.yml
qubit_mq:
    sandbox: false
    producer:
        module: 'sas' # nombre de modulo, va a ser univoco por la aplicacion que use el bundle
    consumers:
        sas: # nombre del consumer
            name: 'user' #'cola que va a rexibir mensajes' #UNA SOLA
            handler:
                - {name: sas.login.user_login, service: servicio_log_in}
                - {name: sas.login.billing, service: servicio_billing}
        billing:
            name: 'job'
            handler:
                - {name: billing.job.biller, service: servicio_billing_biller}
                - {name: billing.job.*, service: servicio_billing_job_rest}
                - {name: billing.#, service: servicio_billing_rest}
```


Aclaración: se deberán tener algunas consideraciones a la hora de especificar la configuración:
- Los nombres de los consumers deben ser diferentes
- Los nombres de las colas no se deben repetir
- Los nombres de los handlers no se deben repetir
- El campo service, es el nombre de un servicio que tiene que estar registrado
- El campo name, tiene como función ser el responsable de filtrar y direccionar los mensajes hacia los servicios especificados. Los mismos se generan en base al module . component . action
[Se les puede pasar "#" o "*"][6]

Para el siguiente caso:
```yaml
{name: billing.job.biller, service: servicio_billing_biller}
```
todo lo que venga con una routing key billing.job.biller será procesado por el servicio servicio_billing_biller

```yaml
# app/config/services_dev.yml|services_prod.yml
services:
    servicio_billing_biller:
        class: AppBundle\Services\BillingService
        ...
    ...
```

Sanbox
---
La configuración acepta el parámetro 'sanbox', en forma de booleano, el mismo proporciona una funcionalidad de switch permitiendo pegarle al rabbit de staging o al productivo.
```yaml
rabbit:
    sandbox: true // Pegada a staging
```
```yaml    
rabbit:
    sandbox: false // Pegada a productivo
```
Si este parámetro no se pasa, se toma por defult en true pegandole a staging.


Uso Consumer
---

Se debe especificar un objeto, el cual debe definir su componente y acción.
La misma clase debe extender de la clase Qubit\Bundle\QubitMqBundle\Events\Message

```php
<?php
// src/FooBundle/Classes/SasLoginMessage.php

use Qubit\Bundle\QubitMqBundle\Events\Message;

class SasLoginMessage extends Message
{
    public $component = 'login';
    public $action = 'user_login';
}
```
Y este mismo objeto, es el que se le enviará al producer.

```php
<?php
// src/FooBundle/Controller/FooController.php

use src/FooBundle/Classes/SasLoginMessage;

$message =  new SasLoginMessage();
$message->setTags(array('login'));
$message->setPayload(array(...));

$this->get('qubit.event.producer')->publish($message);

```
El método publish retornará true si la acción terminó exitosamente y caso contrario retornará un false generando un log en la carpeta app/logs/
El nombre del archivo de log cambiará dependiendo del ambiente y el día, pero no la primera parte teniendo la siguiente nomenclatura: qubit_rabbit_event_*.log


Uso Producers
---

Los mismos son llamados mediante línea de comando.

```bash
$ php app/console rabbitmq:consumer -m 10 nombre_consumer
```
La siguiente línea obtiene los últimos 10 mensajes pasandole el nombre del consumer especificado en la configuración (config.yml)
El consumer mediante la routing key especifica a qué servicio tiene que llamar.


```php
<?php
// AppBundle\Services\BillingService.php

use Qubit\Bundle\QubitMqBundle\Events\Message;

class ConsumerHandler
{
    public function execute(Message $msg)
    {
        ...
        error_log('$msg: ' . print_r($msg, true), 0);
        ...
        // IMPORTANTE:
        // Es el handler de la app, el encargado de retornar TRUE en caso de exito y FALSE en caso de error generando su debido log
        $result = doSomethingMethod();
        if (!result) {
            $logger->log(ERROR);
            return false;
        }
        return true;
    }
}

```
IMPORTANTE:
Es el handler de la app, el encargado de retornar TRUE en caso de exito y FALSE en caso de error generando su debido log.


[1]: http://git.qubit.tv:8888/Qubit/RabbitBundle
[2]: https://github.com/php-amqplib/RabbitMqBundle
[3]: https://www.rabbitmq.com/
[4]: http://git.qubit.tv:8888/Qubit/UtilsBundle
[5]: http://git.qubit.tv:8888/Qubit/LogBundle
[6]: https://www.rabbitmq.com/tutorials/tutorial-five-php.html
