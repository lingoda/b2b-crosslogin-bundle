<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle;

use Lingoda\CrossLoginBundle\Web\Receivers;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Webmozart\Assert\Assert;

class LingodaCrossLoginBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        Assert::isInstanceOf($rootNode, ArrayNodeDefinition::class);
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
                ->arrayNode('audiences')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end() // audiences — extra hosts this app answers to (multi-host apps)
                ->arrayNode('receivers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('default_route')
                                ->isRequired()
                            ->end() // default_route
                            ->scalarNode('error_query_parameter')
                                ->defaultValue('crosslogin_error')
                            ->end() // error_query_parameter
                            ->scalarNode('jwt_manager')
                                ->defaultValue('lexik_jwt_authentication.jwt_manager')
                            ->end() // jwt_manager
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end() // receivers — landing endpoints; empty means this app only sends
            ->end()
        ;
    }

    /**
     * @param array<string|int, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $container->import('../config/controllers.php');
        $container->import('../config/twig.php');
        $container->import('../config/receive.php');

        $this->bindParameters($builder, $this->extensionAlias, $config);

        $audiences = $config['audiences'] ?? [];
        Assert::isArray($audiences);
        // bindParameters skips empty arrays, so set the audiences list explicitly to
        // keep the parameter always defined (the listener service injects it).
        $builder->setParameter($this->extensionAlias . '.audiences', $audiences);

        $receivers = $config['receivers'] ?? [];
        Assert::isMap($receivers);
        // Same reason as audiences: an empty map would otherwise define no parameter at all.
        $builder->setParameter($this->extensionAlias . '.receivers', $receivers);
        $this->registerReceivers($builder, $receivers);
    }

    /**
     * The JWT manager is per-receiver — an app may verify cross-login tokens with a different
     * key than it signs its own with — so the managers go in a locator keyed by receiver name
     * and are only instantiated for the receiver actually being served.
     *
     * @param array<string, mixed> $receivers
     */
    private function registerReceivers(ContainerBuilder $builder, array $receivers): void
    {
        $jwtManagers = [];
        foreach ($receivers as $name => $receiver) {
            Assert::isArray($receiver);
            $jwtManager = $receiver['jwt_manager'] ?? 'lexik_jwt_authentication.jwt_manager';
            Assert::stringNotEmpty($jwtManager);
            $jwtManagers[$name] = new Reference($jwtManager);
        }

        $builder
            ->setDefinition(Receivers::class, new Definition(Receivers::class))
            ->setArguments([
                new Parameter($this->extensionAlias . '.receivers'),
                ServiceLocatorTagPass::register($builder, $jwtManagers),
            ])
        ;
    }

    /**
     * Binds the params from config.
     *
     * Recurses into associative arrays, so a nested config key becomes a dotted parameter name.
     * A list is stored whole, which is also why an empty array stores nothing at all — `$config[0]`
     * is unset either way, so it recurses over zero elements. Callers that need a parameter to
     * exist unconditionally must set it themselves; `audiences` and `receivers` both do.
     */
    public function bindParameters(ContainerBuilder $container, string $alias, mixed $config): void
    {
        if (\is_array($config)) {
            if (empty($config[0])) {
                foreach ($config as $key => $value) {
                    $this->bindParameters($container, $alias . '.' . $key, $value);
                }

                return;
            }

            $container->setParameter($alias, $config);

            return;
        }

        Assert::nullOrScalar($config);
        $container->setParameter($alias, $config);
    }
}
