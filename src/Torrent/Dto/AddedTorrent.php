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
 * The result of a "torrent-add" RPC call.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class AddedTorrent implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $duplicate,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duplicate' => $this->duplicate,
        ];
    }
}
