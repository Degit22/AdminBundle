<?php

namespace Creonit\AdminBundle;

use Creonit\AdminBundle\DependencyInjection\Compiler\ModulePass;
use Creonit\AdminBundle\DependencyInjection\Compiler\PluginPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CreonitAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ModulePass());
        $container->addCompilerPass(new PluginPass());
       
    }


}
