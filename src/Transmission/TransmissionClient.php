<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Transmission;

use Antalaron\Seedlet\Transmission\Exception\TransmissionRpcException;
use Antalaron\Seedlet\Transmission\Exception\TransmissionUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HTTP implementation of the Transmission RPC protocol.
 *
 * Transmission requires every request to carry a session id, obtained from
 * the "X-Transmission-Session-Id" header of a previous "409 Conflict"
 * response. This client transparently negotiates and caches that id.
 *
 * @see https://github.com/transmission/transmission/blob/main/docs/rpc-spec.md
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TransmissionClient implements TransmissionClientInterface
{
    private const SESSION_ID_HEADER = 'x-transmission-session-id';

    private ?string $sessionId = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'TRANSMISSION_URL')]
        private readonly string $url,
        #[Autowire(env: 'TRANSMISSION_USERNAME')]
        private readonly string $username = '',
        #[Autowire(env: 'TRANSMISSION_PASSWORD')]
        private readonly string $password = '',
        #[Autowire(env: 'float:TRANSMISSION_TIMEOUT')]
        private readonly float $timeout = 10.0,
    ) {
    }

    public function request(string $method, array $arguments = []): array
    {
        return $this->doRequest($method, $arguments, true);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function doRequest(string $method, array $arguments, bool $allowRetry): array
    {
        $options = [
            'json' => ['method' => $method, 'arguments' => $arguments],
            'timeout' => $this->timeout,
            'headers' => null !== $this->sessionId ? [self::SESSION_ID_HEADER => $this->sessionId] : [],
        ];

        if ('' !== $this->username) {
            $options['auth_basic'] = [$this->username, $this->password];
        }

        try {
            $response = $this->httpClient->request('POST', $this->url, $options);
            $statusCode = $response->getStatusCode();
        } catch (HttpClientExceptionInterface $exception) {
            $this->logger->error('Could not reach Transmission.', ['exception' => $exception]);

            throw new TransmissionUnavailableException('Could not reach Transmission.', previous: $exception);
        }

        if (409 === $statusCode) {
            $this->sessionId = $response->getHeaders(false)[self::SESSION_ID_HEADER][0] ?? null;

            if (!$allowRetry || null === $this->sessionId) {
                throw new TransmissionUnavailableException('Transmission did not provide a session id.');
            }

            return $this->doRequest($method, $arguments, false);
        }

        try {
            $data = $response->toArray();
        } catch (HttpClientExceptionInterface $exception) {
            $this->logger->error('Transmission returned an unexpected response.', ['exception' => $exception]);

            throw new TransmissionUnavailableException('Transmission returned an unexpected response.', previous: $exception);
        }

        if ('success' !== ($data['result'] ?? null)) {
            throw new TransmissionRpcException($data['result'] ?? 'Transmission reported an unknown error.');
        }

        /** @var array<string, mixed> $arguments */
        $arguments = $data['arguments'] ?? [];

        return $arguments;
    }
}
