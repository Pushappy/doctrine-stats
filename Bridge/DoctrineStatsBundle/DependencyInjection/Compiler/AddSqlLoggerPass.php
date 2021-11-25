<?php

namespace steevanb\DoctrineStats\Bridge\DoctrineStatsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddSqlLoggerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('doctrine.dbal.logger.chain');

        // Append doctrine_stats logger to loggers in LoggerChain constructor
        $loggers = $definition->getArguments()[0];
        array_push($loggers, new Reference('doctrine_stats.logger.sql'));
        $definition->setArgument(0, $loggers);
    }
}
