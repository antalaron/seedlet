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
 * A single file within a torrent.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class TorrentFile
{
    public function __construct(
        public int $index,
        public string $name,
        public int $length,
        public int $bytesCompleted,
        public bool $wanted,
        public int $priority,
    ) {
    }

    /**
     * @param array<string, mixed> $file an entry of the "files" RPC field
     * @param array<string, mixed> $stat the matching entry of the "fileStats" RPC field
     */
    public static function fromRpc(int $index, array $file, array $stat): self
    {
        return new self(
            index: $index,
            name: (string) $file['name'],
            length: (int) $file['length'],
            bytesCompleted: (int) $file['bytesCompleted'],
            wanted: (bool) $stat['wanted'],
            priority: (int) $stat['priority'],
        );
    }
}
