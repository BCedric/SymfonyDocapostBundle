<?php

namespace BCedric\DocapostBundle;

use BCedric\DocapostBundle\Service\DocapostFast;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BCedricDocapostBundle extends AbstractBundle
{

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('pem_file')->end()
            ->scalarNode('url')->end()
            ->scalarNode('siren')->end()
            ->scalarNode('circuitId')->defaultNull()->end()
            ->scalarNode('archives_dir')->defaultNull()->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()->set(DocapostFast::class)
            ->public();
        $builder->autowire(DocapostFast::class)
            ->setArgument('$pem_file', $config['pem_file'])
            ->setArgument('$url', $config['url'])
            ->setArgument('$siren', $config['siren'])
            ->setArgument('$circuitId', $config['circuitId'])
            ->setArgument('$archives_dir', $config['archives_dir']);
    }
}
