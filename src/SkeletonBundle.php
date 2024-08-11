<?php

namespace Codyas\SkeletonBundle;

use Codyas\SkeletonBundle\Compiler\UserRoleProviderInterfacePass;
use Codyas\SkeletonBundle\DependencyInjection\Compiler\TwigPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SkeletonBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new TwigPass());
        $container->addCompilerPass(new UserRoleProviderInterfacePass());
    }
}
