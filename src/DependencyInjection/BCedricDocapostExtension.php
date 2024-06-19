<?php

namespace BCedric\DocapostBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class BCedricDocapostExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $docapost = $container->autowire(
            'bcedric_docapost',
            'BCedric\DocapostBundle\Service\DocapostFast'
        );
        $docapost->setPublic(true);
        $docapost->setArguments(array($config));
    }

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function prepend(ContainerBuilder $container)
    {
        // TODO: Implement prepend() method.
    }
}
