<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class LingodaCrossLoginBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
                ->scalarNode('query_parameter_name')
                    ->defaultValue('bearer')
                ->end() // token_param_name
                ->scalarNode('issuer')
                    ->isRequired()
                ->end() // issuer
                ->integerNode('token_ttl')
                    ->defaultValue(5)
                    ->min(1)->max(10)
                ->end() // token_ttl
            ->end()
        ;
    }

    /**
     * @param array<string|int, bool|int|string|null> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $container->import('../config/controllers.php');
        $container->import('../config/twig.php');

        $this->bindParameters($builder, $this->extensionAlias, $config);
    }

    /**
     * Binds the params from config.
     *
     * @param bool|int|string|array<string|int, bool|int|string|null>|null $config
     */
    public function bindParameters(ContainerBuilder $container, string $alias, array|bool|int|string|null $config): void
    {
        if (\is_array($config) && empty($config[0])) {
            foreach ($config as $key => $value) {
                $this->bindParameters($container, $alias . '.' . $key, $value);
            }
        } else {
            $container->setParameter($alias, $config);
        }
    }
}
