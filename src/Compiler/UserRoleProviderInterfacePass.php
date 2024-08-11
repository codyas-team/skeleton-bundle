<?php

namespace Codyas\SkeletonBundle\Compiler;

use Codyas\SkeletonBundle\Service\UserRoleProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserRoleProviderInterfacePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds(UserRoleProviderInterface::TAG);

        foreach ($taggedServices as $id => $tags) {
            $container->setAlias(UserRoleProviderInterface::class, $id);
            break;
        }
    }
}
