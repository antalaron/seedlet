<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests\Transmission;

use Antalaron\Seedlet\Transmission\Exception\TransmissionRpcException;
use Antalaron\Seedlet\Transmission\Exception\TransmissionUnavailableException;
use Antalaron\Seedlet\Transmission\TransmissionClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TransmissionClientTest extends TestCase
{
    public function testRequestNegotiatesSessionIdAfterConflict(): void
    {
        $responses = [
            new MockResponse('', ['http_code' => 409, 'response_headers' => ['X-Transmission-Session-Id' => 'session-abc']]),
            new MockResponse(json_encode(['result' => 'success', 'arguments' => ['torrents' => []]])),
        ];

        $requestedSessionIds = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requestedSessionIds, &$responses) {
            $requestedSessionIds[] = $options['normalized_headers']['x-transmission-session-id'][0] ?? null;

            return array_shift($responses);
        });

        $client = new TransmissionClient($httpClient, new NullLogger(), 'http://transmission.test/rpc');
        $result = $client->request('torrent-get');

        $this->assertSame(['torrents' => []], $result);
        $this->assertCount(2, $requestedSessionIds);
        $this->assertNull($requestedSessionIds[0]);
    }

    public function testRequestThrowsWhenTransmissionIsUnreachable(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('', ['error' => 'Connection refused'])]);
        $client = new TransmissionClient($httpClient, new NullLogger(), 'http://transmission.test/rpc');

        $this->expectException(TransmissionUnavailableException::class);

        $client->request('torrent-get');
    }

    public function testRequestThrowsOnRpcError(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode(['result' => 'invalid argument', 'arguments' => []])),
        ]);
        $client = new TransmissionClient($httpClient, new NullLogger(), 'http://transmission.test/rpc');

        $this->expectException(TransmissionRpcException::class);

        $client->request('torrent-add', ['filename' => 'not-valid']);
    }

    public function testRequestSendsBasicAuthWhenCredentialsProvided(): void
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) {
            self::assertSame(
                ['Authorization: Basic '.base64_encode('transmission:secret')],
                $options['normalized_headers']['authorization'] ?? null,
            );

            return new MockResponse(json_encode(['result' => 'success', 'arguments' => []]));
        });

        $client = new TransmissionClient($httpClient, new NullLogger(), 'http://transmission.test/rpc', 'transmission', 'secret');
        $client->request('session-get');
    }
}
