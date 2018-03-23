<?php

namespace Qubit\Bundle\QubitMqBundle;

use Qubit\Bundle\QubitMqBundle\DependencyInjection\Compiler\QubitMqCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class QubitMqBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new QubitMqCompilerPass());
    }
}
