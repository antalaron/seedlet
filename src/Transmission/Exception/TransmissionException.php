<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Transmission\Exception;

/**
 * Base exception for every error raised by the Transmission RPC layer.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
abstract class TransmissionException extends \RuntimeException
{
}
