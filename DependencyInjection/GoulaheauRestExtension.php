<?php

namespace Goulaheau\RestBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Parser as YamlParser;

class GoulaheauRestExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $yamlParser = new YamlParser();

        $doctrineConfig = $yamlParser->parse(
            file_get_contents(__DIR__ . '/../Resources/config/packages/doctrine.yaml')
        );
        $container->prependExtensionConfig('doctrine', $doctrineConfig['doctrine']);
    }
}
