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

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Requires a valid CSRF token on every state-changing "/api/" request.
 *
 * The token is issued to the page as "csrf_token('api')" and must be sent
 * back by the frontend in the "X-Csrf-Token" header.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
#[AsEventListener]
final readonly class CsrfProtectionListener
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/') || \in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return;
        }

        $token = $request->headers->get('X-Csrf-Token', '');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('api', $token))) {
            throw new AccessDeniedHttpException('Invalid or missing CSRF token.');
        }
    }
}
