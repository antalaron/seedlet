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

use Antalaron\Seedlet\Torrent\Request\SessionUpdateRequest;
use Antalaron\Seedlet\Torrent\TorrentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JSON API exposing Transmission's global session settings, i.e. the
 * global speed limits and the turtle/alt-speed mode.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
#[Route(path: '/api/session', name: 'api_session_')]
final class SessionController extends AbstractController
{
    public function __construct(
        private readonly TorrentManager $torrentManager,
    ) {
    }

    #[Route(path: '', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return $this->json(['session' => $this->torrentManager->getSessionSettings()]);
    }

    #[Route(path: '', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $payload = '' === $request->getContent() ? [] : $request->toArray();

        $this->torrentManager->updateSessionSettings(SessionUpdateRequest::fromArray($payload));

        return $this->json(['session' => $this->torrentManager->getSessionSettings()]);
    }
}
