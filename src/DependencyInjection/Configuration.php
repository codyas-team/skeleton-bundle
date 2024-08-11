<?php

namespace Codyas\SkeletonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges the bundle configuration
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('skeleton');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->append($this->getSecurityConfig())
                ->append($this->getTemplatingConfig())
//                ->append($this->getKnpMenuConfig())
//                ->append($this->getRouteAliasesConfig())
                ->arrayNode('icons')
                    ->defaultValue([])
                    ->scalarPrototype()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    private function getSecurityConfig(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('security');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('registration')
                    ->defaultFalse()
                ->end()
                ->booleanNode('password_recovery')
                    ->defaultFalse()
                ->end()
                ->scalarNode('user_provider')
                    ->defaultNull()
                ->end()
            ->end()
        ->end();

        return $rootNode;
    }

    private function getTemplatingConfig(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('templating');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('name')
                    ->defaultNull()
                ->end()
                ->scalarNode('description')
                    ->defaultNull()
                ->end()
                ->scalarNode('keywords')
                    ->defaultNull()
                ->end()
                ->scalarNode('home_path')
                    ->defaultNull()
                ->end()
                ->scalarNode('home_label')
                    ->defaultNull()
                ->end()
            ->end()
        ->end();

        return $rootNode;
    }
}
