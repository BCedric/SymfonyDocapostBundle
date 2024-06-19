<?php

namespace BCedric\DocapostBundle;

use BCedric\DocapostBundle\DependencyInjection\BCedricDocapostExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BCedricDocapostBundle extends Bundle
{

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('pem_file')->end()
            ->scalarNode('pem_password')->end()
            ->scalarNode('url')->end()
            ->scalarNode('siren')->end()
            ->scalarNode('circuitId')->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->get('b_cedric_docapost.docapost_fast')
            ->arg('pem_file', $config['pem_file'])
            ->arg('pem_password', $config['pem_password'])
            ->arg('url', $config['url'])
            ->arg('siren', $config['siren'])
            ->arg('circuitId', $config['circuitId']);
    }
}
