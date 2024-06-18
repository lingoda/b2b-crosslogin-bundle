<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests;

use Lingoda\CrossLoginBundle\LingodaCrossLoginBundle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
        ], (new Processor())->processConfiguration($configuration, [
            'lingoda_cross_login' => [
                'query_parameter_name' => 'token_name',
                'issuer' => 'issuer',
                'token_ttl' => 10,
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
            ]
        ], $container);

        self::assertSame('token_name', $container->getParameter('lingoda_cross_login.query_parameter_name'));
        self::assertSame('issuer', $container->getParameter('lingoda_cross_login.issuer'));
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
            ->getConfiguration([], new ContainerBuilder(new ParameterBag()))
        ;
    }
}
