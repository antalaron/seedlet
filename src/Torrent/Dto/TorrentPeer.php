<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Torrent\Dto;

/**
 * A peer currently connected for a torrent.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class TorrentPeer
{
    public function __construct(
        public string $address,
        public string $clientName,
        public float $progress,
        public int $rateToClient,
        public int $rateToPeer,
        public bool $isDownloadingFrom,
        public bool $isUploadingTo,
    ) {
    }

    /**
     * @param array<string, mixed> $peer an entry of the "peers" RPC field
     */
    public static function fromRpc(array $peer): self
    {
        return new self(
            address: (string) $peer['address'],
            clientName: (string) $peer['clientName'],
            progress: (float) $peer['progress'],
            rateToClient: (int) $peer['rateToClient'],
            rateToPeer: (int) $peer['rateToPeer'],
            isDownloadingFrom: (bool) $peer['isDownloadingFrom'],
            isUploadingTo: (bool) $peer['isUploadingTo'],
        );
    }
}
