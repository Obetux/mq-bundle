<?php

namespace Qubit\Bundle\QubitMqBundle\Tests\Core;

use Symfony\Bundle\MonologBundle\MonologBundle;
use Qubit\Bundle\QubitMqBundle\QubitMqBundle;
use OldSound\RabbitMqBundle\OldSoundRabbitMqBundle;
use Qubit\Bundle\UtilsBundle\UtilsBundle;
use Qubit\Bundle\LogBundle\LogBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    /**
     * registerBundles
     *
     * @return array Array of bundles
     */
    public function registerBundles()
    {
        $bundles = array();

        // Cargo bundles necesarios para el container.
        if (in_array($this->getEnvironment(), array('test'))) {
            $bundles[] = new MonologBundle();
            $bundles[] = new UtilsBundle();
            $bundles[] = new LogBundle();
            $bundles[] = new OldSoundRabbitMqBundle();
            $bundles[] = new QubitMqBundle();
        }

        return $bundles;
    }

    /**
     * registerContainerConfiguration
     *
     * @param object $loader LoaderInterface Object
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config.yml');
    }
}
