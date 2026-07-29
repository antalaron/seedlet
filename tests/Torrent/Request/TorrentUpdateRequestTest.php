<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests\Torrent\Request;

use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;
use Antalaron\Seedlet\Torrent\Request\TorrentUpdateRequest;
use PHPUnit\Framework\TestCase;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TorrentUpdateRequestTest extends TestCase
{
    public function testFromArrayMapsProvidedFields(): void
    {
        $request = TorrentUpdateRequest::fromArray([
            'priority' => 1,
            'downloadDir' => '/data/downloads',
            'seedRatioLimit' => 1.5,
            'seedRatioMode' => 1,
            'seedIdleLimit' => 30,
            'seedIdleMode' => 1,
            'peerLimit' => 40,
        ]);

        $this->assertSame(1, $request->bandwidthPriority);
        $this->assertSame('/data/downloads', $request->downloadDir);
        $this->assertSame(1.5, $request->seedRatioLimit);
        $this->assertSame(40, $request->peerLimit);
    }

    public function testFromArrayLeavesUnprovidedFieldsNull(): void
    {
        $request = TorrentUpdateRequest::fromArray([]);

        $this->assertNull($request->bandwidthPriority);
        $this->assertNull($request->downloadDir);
        $this->assertNull($request->peerLimit);
    }

    public function testFromArrayRejectsInvalidPriority(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        TorrentUpdateRequest::fromArray(['priority' => 5]);
    }

    public function testFromArrayRejectsInvalidSeedRatioMode(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        TorrentUpdateRequest::fromArray(['seedRatioMode' => 9]);
    }

    public function testFromArrayRejectsNegativePeerLimit(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        TorrentUpdateRequest::fromArray(['peerLimit' => 0]);
    }

    public function testFromArrayRejectsEmptyDownloadDir(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        TorrentUpdateRequest::fromArray(['downloadDir' => '   ']);
    }
}
