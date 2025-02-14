<?php

namespace Creonit\AdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class CreonitAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $manager = $container->getDefinition('creonit_admin');
        $manager->addMethodCall('setTitle', [$config['title']]);
        if(isset($config['icon'])){
            $manager->addMethodCall('setIcon', [$config['icon']]);
        }
        if(isset($config['modules']) and is_array($config['modules'])){
            $manager->addMethodCall('setModulesConfig', [$config['modules']]);
        }
        
        
    }
}
