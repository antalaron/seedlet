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
 * Thrown when user-provided input for a torrent operation is invalid.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class InvalidTorrentRequestException extends \InvalidArgumentException
{
}
