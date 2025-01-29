<?php

namespace BCedric\DocapostBundle;

use BCedric\DocapostBundle\Command\SyncDocapostUsersCommand;
use BCedric\DocapostBundle\Repository\DocapostUserRepository;
use BCedric\DocapostBundle\Service\DocapostFast;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

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
        $builder->register(SyncDocapostUsersCommand::class)
            ->setClass(SyncDocapostUsersCommand::class)
            ->addTag('console.command')
            ->addArgument(new Reference(DocapostFast::class))
            ->addArgument(new Reference(DocapostUserRepository::class))
            ->addArgument(new Reference(EntityManagerInterface::class))
        ;

        $builder->register(DocapostUserRepository::class)
            ->setClass(DocapostUserRepository::class)
            ->addArgument(new Reference(ManagerRegistry::class))
        ;

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
