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
 * Transmission torrent status codes, as returned by the "status" RPC field.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
enum TorrentStatus: int
{
    case Stopped = 0;
    case QueuedToVerify = 1;
    case Verifying = 2;
    case QueuedToDownload = 3;
    case Downloading = 4;
    case QueuedToSeed = 5;
    case Seeding = 6;

    public function label(): string
    {
        return match ($this) {
            self::Stopped => 'Stopped',
            self::QueuedToVerify => 'Queued to verify',
            self::Verifying => 'Verifying',
            self::QueuedToDownload => 'Queued',
            self::Downloading => 'Downloading',
            self::QueuedToSeed => 'Queued to seed',
            self::Seeding => 'Seeding',
        };
    }
}
