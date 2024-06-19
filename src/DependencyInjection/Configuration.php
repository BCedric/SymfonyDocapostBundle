<?php

namespace BCedric\DocapostBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bcedric_docapost');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->scalarNode('pem_file')->end()
            ->scalarNode('pem_password')->end()
            ->scalarNode('url')->end()
            ->scalarNode('siren')->end()
            ->scalarNode('circuitId')->end()
            ->end();

        return $treeBuilder;
    }
}
