<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Controller;

use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;
use Antalaron\Seedlet\Torrent\Request\AddTorrentRequest;
use Antalaron\Seedlet\Torrent\Request\FileSelectionRequest;
use Antalaron\Seedlet\Torrent\Request\TorrentUpdateRequest;
use Antalaron\Seedlet\Torrent\TorrentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JSON API used by the frontend to list, inspect and control torrents.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
#[Route(path: '/api/torrents')]
final class TorrentController extends AbstractController
{
    public function __construct(
        private readonly TorrentManager $torrentManager,
    ) {
    }

    #[Route(path: '', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(['torrents' => $this->torrentManager->listTorrents()]);
    }

    #[Route(path: '', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (null !== $uploadedFile) {
            $content = file_get_contents($uploadedFile->getPathname());
            $startPaused = $this->parseBoolean($request->request->get('startPaused'));
            $addRequest = AddTorrentRequest::fromTorrentFileContent(false !== $content ? $content : '', $startPaused);
        } else {
            $payload = $this->decodePayload($request);
            $startPaused = $this->parseBoolean($payload['startPaused'] ?? false);
            $addRequest = AddTorrentRequest::fromUri((string) ($payload['source'] ?? ''), $startPaused);
        }

        $added = $this->torrentManager->addTorrent($addRequest);

        return $this->json(['torrent' => $added], Response::HTTP_CREATED);
    }

    #[Route(path: '/{id<\d+>}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->json(['torrent' => $this->torrentManager->getTorrent($id)]);
    }

    #[Route(path: '/{id<\d+>}', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->torrentManager->updateTorrent($id, TorrentUpdateRequest::fromArray($this->decodePayload($request)));

        return $this->json(['torrent' => $this->torrentManager->getTorrent($id)]);
    }

    #[Route(path: '/{id<\d+>}/files', methods: ['PATCH'])]
    public function updateFiles(int $id, Request $request): JsonResponse
    {
        $this->torrentManager->updateFiles($id, FileSelectionRequest::fromArray($this->decodePayload($request)));

        return $this->json(['torrent' => $this->torrentManager->getTorrent($id)]);
    }

    #[Route(path: '/{id<\d+>}/pause', methods: ['POST'])]
    public function pause(int $id): JsonResponse
    {
        $this->torrentManager->pauseTorrent($id);

        return $this->json(['status' => 'ok']);
    }

    #[Route(path: '/{id<\d+>}/resume', methods: ['POST'])]
    public function resume(int $id): JsonResponse
    {
        $this->torrentManager->resumeTorrent($id);

        return $this->json(['status' => 'ok']);
    }

    #[Route(path: '/{id<\d+>}', methods: ['DELETE'])]
    public function remove(int $id, Request $request): JsonResponse
    {
        $payload = $this->decodePayload($request);

        if (!\is_bool($payload['deleteLocalData'] ?? false)) {
            throw new InvalidTorrentRequestException('"deleteLocalData" must be a boolean.');
        }

        $this->torrentManager->removeTorrent($id, $payload['deleteLocalData'] ?? false);

        return $this->json(['status' => 'ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        return '' === $request->getContent() ? [] : $request->toArray();
    }

    /**
     * Accepts native booleans (from decoded JSON payloads) as well as the
     * string values sent by multipart/form-data requests (file uploads),
     * where every field arrives as a string.
     */
    private function parseBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array($value, ['1', 'true', 'on', 'yes'], true);
    }
}
