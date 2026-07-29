<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Torrent\Exception;

/**
 * Thrown when a requested torrent id does not exist (anymore) in Transmission.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TorrentNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(\sprintf('Torrent "%d" was not found.', $id));
    }
}
