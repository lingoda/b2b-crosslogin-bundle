<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests;

use Lingoda\CrossLoginBundle\LingodaCrossLoginBundle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class LingodaCrossLoginBundleTest extends TestCase
{
    #[Test]
    public function configure(): void
    {
        $configuration = $this->getConfiguration();

        // empty config
        self::assertSame([
            'query_parameter_name' => 'bearer',
            'issuer' => null,
        ], (new Processor())->processConfiguration($configuration, []));

        // non-empty config
        self::assertSame([
            'query_parameter_name' => 'token_name',
            'issuer' => 'issuer',
        ], (new Processor())->processConfiguration($configuration, [
            'lingoda_cross_login' => [
                'query_parameter_name' => 'token_name',
                'issuer' => 'issuer',
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
                'query_parameter_name' => 'token_name'
            ]
        ], $container);

        self::assertSame('token_name', $container->getParameter('lingoda_cross_login.query_parameter_name'));
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
        /** @phpstan-ignore-next-line */
        return (new LingodaCrossLoginBundle())
            ->getContainerExtension()
            ->getConfiguration([], new ContainerBuilder(new ParameterBag()));
    }
}
