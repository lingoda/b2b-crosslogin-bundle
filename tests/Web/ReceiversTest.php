<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\Web;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lingoda\CrossLoginBundle\Web\Exception\UnknownReceiverException;
use Lingoda\CrossLoginBundle\Web\Receivers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ReceiversTest extends TestCase
{
    #[Test]
    public function resolvesAConfiguredReceiver(): void
    {
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $receiver = $this->receivers($jwtManager)->get('first_app');

        self::assertSame('first_app', $receiver->name);
        self::assertSame('first_app_main', $receiver->defaultRoute);
        self::assertSame('crosslogin_error', $receiver->errorQueryParameter);
        self::assertSame($jwtManager, $receiver->jwtManager);
    }

    #[Test]
    public function anUnconfiguredReceiverNamesWhatIsConfigured(): void
    {
        self::expectException(UnknownReceiverException::class);
        self::expectExceptionMessage('Cross-login receiver "second_app" is not configured.');
        self::expectExceptionMessage('Configured receivers: first_app.');

        $this->receivers($this->createMock(JWTTokenManagerInterface::class))->get('second_app');
    }

    #[Test]
    public function anEmptyRegistrySaysSoRatherThanListingNothing(): void
    {
        self::expectException(UnknownReceiverException::class);
        self::expectExceptionMessage('Configured receivers: <none>.');

        (new Receivers([], new ServiceLocator([])))->get('first_app');
    }

    #[Test]
    public function aJwtManagerOfTheWrongTypeIsRejected(): void
    {
        $receivers = new Receivers(
            ['first_app' => [
                'default_route' => 'first_app_main',
                'error_query_parameter' => 'crosslogin_error',
                'jwt_manager' => 'app.not_a_jwt_manager',
            ]],
            new ServiceLocator(['first_app' => static fn (): \stdClass => new \stdClass()]),
        );

        self::expectException(UnknownReceiverException::class);
        self::expectExceptionMessage('app.not_a_jwt_manager');

        $receivers->get('first_app');
    }

    private function receivers(JWTTokenManagerInterface $jwtManager): Receivers
    {
        return new Receivers(
            ['first_app' => [
                'default_route' => 'first_app_main',
                'error_query_parameter' => 'crosslogin_error',
                'jwt_manager' => 'lexik_jwt_authentication.jwt_manager',
            ]],
            new ServiceLocator(['first_app' => static fn (): JWTTokenManagerInterface => $jwtManager]),
        );
    }
}
