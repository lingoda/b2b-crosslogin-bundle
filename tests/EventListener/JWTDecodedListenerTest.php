<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Tests\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lingoda\CrossLoginBundle\EventListener\JWTDecodedListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JWTDecodedListenerTest extends TestCase
{
    #[Test]
    public function onJWTDecodedWithoutAud(): void
    {
        $listener = new JWTDecodedListener('audience');
        $event = $this->createMock(JWTDecodedEvent::class);
        $event->expects(self::once())->method('getPayload')->willReturn(['iss' => 'issuer']);
        $event->expects(self::never())->method('markAsInvalid');

        $listener->onJWTDecoded($event);
    }

    #[Test]
    public function onJWTDecodedWithoutIss(): void
    {
        $listener = new JWTDecodedListener('audience');
        $event = $this->createMock(JWTDecodedEvent::class);
        $event->expects(self::once())->method('getPayload')->willReturn(['aud' => 'audience']);
        $event->expects(self::never())->method('markAsInvalid');

        $listener->onJWTDecoded($event);
    }

    #[Test]
    public function onJWTDecodedWithEmptyAudience(): void
    {
        $listener = new JWTDecodedListener('audience');
        $event = $this->createMock(JWTDecodedEvent::class);
        $event->expects(self::once())->method('getPayload')->willReturn(['aud' => [''], 'iss' => 'issuer']);
        $event->expects(self::once())->method('markAsInvalid');

        $listener->onJWTDecoded($event);
    }

    #[Test]
    public function onJWTDecodedWithNonMatchingAudience(): void
    {
        $listener = new JWTDecodedListener('another-audience');
        $event = $this->createMock(JWTDecodedEvent::class);
        $event->expects(self::once())->method('getPayload')->willReturn(['aud' => ['audience'], 'iss' => 'issuer']);
        $event->expects(self::once())->method('markAsInvalid');

        $listener->onJWTDecoded($event);
    }

    #[Test]
    public function onJWTDecodedWithAudienceAsString(): void
    {
        $listener = new JWTDecodedListener('audience');
        $event = $this->createMock(JWTDecodedEvent::class);
        $event->expects(self::once())->method('getPayload')->willReturn(['aud' => 'audience', 'iss' => 'issuer']);
        $event->expects(self::never())->method('markAsInvalid');

        $listener->onJWTDecoded($event);
    }

    #[Test]
    public function onJWTDecoded(): void
    {
        $listener = new JWTDecodedListener('audience');
        $event = $this->createMock(JWTDecodedEvent::class);
        $event->expects(self::once())->method('getPayload')->willReturn(['aud' => ['audience'], 'iss' => 'issuer']);
        $event->expects(self::never())->method('markAsInvalid');

        $listener->onJWTDecoded($event);
    }
}
