<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests\EventListener;

use Antalaron\Seedlet\EventListener\CsrfProtectionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class CsrfProtectionListenerTest extends TestCase
{
    public function testItRejectsRequestsWithInvalidToken(): void
    {
        $tokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $tokenManager->method('isTokenValid')->willReturn(false);

        $listener = new CsrfProtectionListener($tokenManager);

        $this->expectException(AccessDeniedHttpException::class);

        $listener($this->createEvent('POST', '/api/torrents/1/pause'));
    }

    public function testItAcceptsRequestsWithValidToken(): void
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(static fn (CsrfToken $token): bool => 'api' === $token->getId() && 'valid-token' === $token->getValue()))
            ->willReturn(true)
        ;

        $listener = new CsrfProtectionListener($tokenManager);
        $event = $this->createEvent('POST', '/api/torrents/1/pause', 'valid-token');
        $listener($event);

        $this->assertNull($event->getResponse());
    }

    public function testItIgnoresSafeMethods(): void
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->expects($this->never())->method('isTokenValid');

        $listener = new CsrfProtectionListener($tokenManager);
        $listener($this->createEvent('GET', '/api/torrents'));
    }

    public function testItIgnoresNonApiPaths(): void
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->expects($this->never())->method('isTokenValid');

        $listener = new CsrfProtectionListener($tokenManager);
        $listener($this->createEvent('POST', '/some/other/path'));
    }

    private function createEvent(string $method, string $path, string $token = ''): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create($path, $method);
        $request->headers->set('X-Csrf-Token', $token);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
