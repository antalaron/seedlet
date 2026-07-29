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

use Antalaron\Seedlet\Transmission\Exception\TransmissionException;

/**
 * Low-level access to the Transmission RPC protocol.
 *
 * Implementations are responsible for the session id handshake and for
 * translating transport/protocol failures into {@see TransmissionException}.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
interface TransmissionClientInterface
{
    /**
     * Calls a Transmission RPC method and returns its "arguments" payload.
     *
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     *
     * @throws TransmissionException
     */
    public function request(string $method, array $arguments = []): array;
}
