<?php

namespace Deploy;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    const FILENAME = 'deploy.json';

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy');
        $rootNode
            ->children()
                ->append($this->getTargetsNode())
            ->end()
        ;
        return $treeBuilder;
    }

    public function getTargetsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('targets');
        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->append($this->getConnectionNode())
                ->scalarNode('deploy_path')->isRequired()->defaultNull()->end()
                ->scalarNode('deploy_file')->isRequired()->defaultNull()->end()
                ->integerNode('keep_releases')->min(0)->defaultValue(3)->end()
                ->append($this->getCommandsNode())
            ->end()
        ;
        return $node;
    }

    public function getConnectionNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('connection');
        $node
            ->isRequired()
            ->children()
                ->scalarNode('hostname')->isRequired()->defaultNull()->end()
                ->scalarNode('port')->defaultValue(22)->end()
                ->scalarNode('timeout')->defaultValue(30)->end()
                ->scalarNode('username')->isRequired()->defaultNull()->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('passwordfile')->defaultNull()->end()
                ->scalarNode('keyfile')->defaultNull()->end()
            ->end()
        ;
        return $node;
    }

    public function getCommandsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node= $treeBuilder->root('commands');
        $node
            ->children()
                ->arrayNode('pre_sync')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('post_sync')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;
        return $node;
    }
}