<?php

namespace Qubit\Bundle\QubitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        
        $treeBuilder
            ->root('qubit_mq')
                ->children()
                    ->booleanNode('sandbox')->defaultTrue()->end()
                    ->append($this->createProducersNodes())
                    ->append($this->createConsumersNodes())
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
    
    /**
     * createProducersNodes
     *
     * @return object Tree Builder Object
     */
    private function createProducersNodes()
    {
        $httpClientsTreeBuilder = new TreeBuilder();

        $httpClientsNode = $httpClientsTreeBuilder->root('producer');

        $httpClientsNode
            ->children()
                ->scalarNode('module')->defaultValue(null)->end()
            ->end()
        ->end()
        ;

        return $httpClientsNode;
    }
    
    /**
     * createConsumersNodes
     *
     * @return object Tree Builder Object
     */
    private function createConsumersNodes()
    {
        $httpClientsTreeBuilder = new TreeBuilder();

        $httpClientsNode = $httpClientsTreeBuilder->root('consumers');

        $httpClientsNode
            ->prototype('array')
                ->children()
                    ->scalarNode('name')->defaultValue(null)->end()
                    ->arrayNode('handler')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')->defaultValue(null)->end()
                                ->scalarNode('service')->defaultValue(null)->end()
                            ->end()
                        ->end()
                    ->end()
            ->end()
        ->end()
        ;

        return $httpClientsNode;
    }
}
