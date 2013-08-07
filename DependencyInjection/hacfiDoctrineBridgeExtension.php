<?php

namespace hacfi\Bundle\DoctrineBridgeBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class hacfiDoctrineBridgeExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $cacheDirectoryUnresolved = $container->getParameterBag()->get('doctrine_bridge.cache_dir');
        $cacheDirectory = $container->getParameterBag()->resolveValue($cacheDirectoryUnresolved);
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }
    }
}
