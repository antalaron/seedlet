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

use Antalaron\Seedlet\EventListener\ApiExceptionListener;
use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;
use Antalaron\Seedlet\Torrent\Exception\TorrentNotFoundException;
use Antalaron\Seedlet\Transmission\Exception\TransmissionRpcException;
use Antalaron\Seedlet\Transmission\Exception\TransmissionUnavailableException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class ApiExceptionListenerTest extends TestCase
{
    #[DataProvider('provideExceptions')]
    public function testItMapsExceptionsToJsonResponses(\Throwable $exception, int $expectedStatus): void
    {
        $event = $this->createEvent('/api/torrents', $exception);

        (new ApiExceptionListener(new NullLogger()))($event);

        $response = $event->getResponse();

        $this->assertNotNull($response);
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertArrayHasKey('error', json_decode($response->getContent(), true));
    }

    public static function provideExceptions(): iterable
    {
        yield 'torrent not found' => [new TorrentNotFoundException(1), 404];

        yield 'invalid request' => [new InvalidTorrentRequestException('bad input'), 400];

        yield 'transmission unavailable' => [new TransmissionUnavailableException('down'), 503];

        yield 'transmission rpc error' => [new TransmissionRpcException('invalid argument'), 502];

        yield 'routing not found' => [new NotFoundHttpException('no route'), 404];

        yield 'unexpected error' => [new \RuntimeException('boom'), 500];
    }

    public function testItIgnoresNonApiRequests(): void
    {
        $event = $this->createEvent('/', new \RuntimeException('boom'));

        (new ApiExceptionListener(new NullLogger()))($event);

        $this->assertNull($event->getResponse());
    }

    private function createEvent(string $path, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create($path);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
