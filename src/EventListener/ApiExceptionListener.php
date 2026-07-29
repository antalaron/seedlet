<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\EventListener;

use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;
use Antalaron\Seedlet\Torrent\Exception\TorrentNotFoundException;
use Antalaron\Seedlet\Transmission\Exception\TransmissionRpcException;
use Antalaron\Seedlet\Transmission\Exception\TransmissionUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts exceptions raised while handling "/api/" requests into a JSON
 * error response, so raw exceptions/stack traces are never exposed to users.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        [$status, $message] = $this->resolve($exception);

        if (500 <= $status) {
            $this->logger->error('Unhandled error while serving an API request.', ['exception' => $exception]);
        }

        $event->setResponse(new JsonResponse(['error' => $message], $status));
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function resolve(\Throwable $exception): array
    {
        return match (true) {
            $exception instanceof TorrentNotFoundException => [404, $exception->getMessage()],
            $exception instanceof InvalidTorrentRequestException => [400, $exception->getMessage()],
            $exception instanceof JsonException => [400, 'The request body is not valid JSON.'],
            $exception instanceof TransmissionUnavailableException => [503, 'Transmission is currently unavailable. Please try again shortly.'],
            $exception instanceof TransmissionRpcException => [502, \sprintf('Transmission rejected the request: %s', $exception->getMessage())],
            $exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500 => [$exception->getStatusCode(), $exception->getMessage()],
            default => [500, 'An unexpected error occurred.'],
        };
    }
}
