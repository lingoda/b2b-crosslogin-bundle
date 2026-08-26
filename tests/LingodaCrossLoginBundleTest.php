<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests;

use Lingoda\CrossLoginBundle\LingodaCrossLoginBundle;
use Lingoda\CrossLoginBundle\Web\Receivers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Webmozart\Assert\Assert;

class LingodaCrossLoginBundleTest extends TestCase
{
    /**
     * @return iterable<string, array{0: array{issuer?:string, query_parameter_name?:string, token_ttl?:int}, 1: string}>
     */
    public static function incorrectConfigurationData(): iterable
    {
        yield 'no issuer' => [[], 'The child config "issuer" under "lingoda_cross_login" must be configured.'];
        yield 'invalid min token_ttl' => [['issuer' => 'issuer', 'token_ttl' => 0], 'The value 0 is too small for path "lingoda_cross_login.token_ttl". Should be greater than or equal to 1'];
        yield 'invalid max token_ttl' => [['issuer' => 'issuer', 'token_ttl' => 11], 'The value 11 is too big for path "lingoda_cross_login.token_ttl". Should be less than or equal to 10'];
    }

    /**
     * @param array{issuer?:string, query_parameter_name?:string, token_ttl?:int} $config
     */
    #[Test]
    #[DataProvider('incorrectConfigurationData')]
    public function incompleteConfigurationThrowsException(array $config, string $exceptionMessage): void
    {
        $configuration = $this->getConfiguration();
        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage($exceptionMessage);

        (new Processor())->processConfiguration($configuration, [
            'lingoda_cross_login' => $config
        ]);
    }

    #[Test]
    public function configure(): void
    {
        $configuration = $this->getConfiguration();

        // minimum config
        self::assertSame([
            'issuer' => 'issuer',
            'query_parameter_name' => 'bearer',
            'token_ttl' => 5,
            'audiences' => [],
            'receivers' => [],
        ], (new Processor())->processConfiguration($configuration, [
            'lingoda_cross_login' => [
                'issuer' => 'issuer',
            ]
        ]));

        // full config
        self::assertSame([
            'query_parameter_name' => 'token_name',
            'issuer' => 'issuer',
            'token_ttl' => 10,
            'audiences' => ['first.host', 'second.host'],
            'receivers' => [
                'first_app' => [
                    'default_route' => 'first_app_main',
                    'error_query_parameter' => 'crosslogin_error',
                    'jwt_manager' => 'lexik_jwt_authentication.jwt_manager',
                ],
            ],
        ], (new Processor())->processConfiguration($configuration, [
            'lingoda_cross_login' => [
                'query_parameter_name' => 'token_name',
                'issuer' => 'issuer',
                'token_ttl' => 10,
                'audiences' => ['first.host', 'second.host'],
                'receivers' => [
                    'first_app' => ['default_route' => 'first_app_main'],
                ],
            ]
        ]));
    }

    #[Test]
    public function load(): void
    {
        $bundle = new LingodaCrossLoginBundle();
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.environment' => 'test',
            'kernel.build_dir' => sys_get_temp_dir(),
        ]));
        $bundle->getContainerExtension()?->load([
            'lingoda_cross_login' => [
                'query_parameter_name' => 'token_name',
                'issuer' => 'issuer',
                'audiences' => ['first.host', 'second.host'],
            ]
        ], $container);

        self::assertSame('token_name', $container->getParameter('lingoda_cross_login.query_parameter_name'));
        self::assertSame('issuer', $container->getParameter('lingoda_cross_login.issuer'));
        self::assertSame(['first.host', 'second.host'], $container->getParameter('lingoda_cross_login.audiences'));
    }

    #[Test]
    public function loadDefaultsAudiencesToEmptyWhenNotConfigured(): void
    {
        $bundle = new LingodaCrossLoginBundle();
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.environment' => 'test',
            'kernel.build_dir' => sys_get_temp_dir(),
        ]));
        $bundle->getContainerExtension()?->load([
            'lingoda_cross_login' => ['issuer' => 'issuer'],
        ], $container);

        // Back-compat: apps that don't configure audiences get an empty param,
        // and the listener then falls back to validating against [issuer].
        self::assertSame([], $container->getParameter('lingoda_cross_login.audiences'));
    }

    #[Test]
    public function loadRegistersNoReceiversByDefault(): void
    {
        $bundle = new LingodaCrossLoginBundle();
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.environment' => 'test',
            'kernel.build_dir' => sys_get_temp_dir(),
        ]));
        $bundle->getContainerExtension()?->load([
            'lingoda_cross_login' => ['issuer' => 'issuer'],
        ], $container);

        // An app that only sends must be able to upgrade without gaining an endpoint.
        self::assertSame([], $container->getParameter('lingoda_cross_login.receivers'));
    }

    #[Test]
    public function loadBindsEachReceiverToItsOwnJwtManager(): void
    {
        $bundle = new LingodaCrossLoginBundle();
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.environment' => 'test',
            'kernel.build_dir' => sys_get_temp_dir(),
        ]));
        $bundle->getContainerExtension()?->load([
            'lingoda_cross_login' => [
                'issuer' => 'issuer',
                'receivers' => [
                    'first_app' => ['default_route' => 'first_app_main'],
                    'second_app' => ['default_route' => 'second_app_main', 'jwt_manager' => 'app.other_jwt_manager'],
                ],
            ],
        ], $container);

        self::assertSame([
            'first_app' => [
                'default_route' => 'first_app_main',
                'error_query_parameter' => 'crosslogin_error',
                'jwt_manager' => 'lexik_jwt_authentication.jwt_manager',
            ],
            'second_app' => [
                'default_route' => 'second_app_main',
                'jwt_manager' => 'app.other_jwt_manager',
                'error_query_parameter' => 'crosslogin_error',
            ],
        ], $container->getParameter('lingoda_cross_login.receivers'));

        self::assertTrue($container->hasDefinition(Receivers::class));
    }

    #[Test]
    public function bindParameters(): void
    {
        $bundle = new LingodaCrossLoginBundle();
        $alias = (string) $bundle->getContainerExtension()?->getAlias();
        self::assertEquals('lingoda_cross_login', $alias);

        $container = new ContainerBuilder(new ParameterBag());
        $config = ['key' => 'value'];
        $bundle->bindParameters($container, $alias, $config);

        self::assertTrue($container->hasParameter('lingoda_cross_login.key'));
        self::assertEquals('value', $container->getParameter('lingoda_cross_login.key'));
    }

    private function getConfiguration(): ConfigurationInterface
    {
        $extension = (new LingodaCrossLoginBundle())->getContainerExtension();
        Assert::isInstanceOf($extension, Extension::class);

        $configuration = $extension->getConfiguration([], new ContainerBuilder(new ParameterBag()));
        Assert::isInstanceOf($configuration, ConfigurationInterface::class);

        return $configuration;
    }
}
