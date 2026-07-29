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
use Antalaron\Seedlet\Torrent\Request\AddTorrentRequest;
use PHPUnit\Framework\TestCase;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class AddTorrentRequestTest extends TestCase
{
    public function testFromUriAcceptsMagnetLink(): void
    {
        $request = AddTorrentRequest::fromUri('magnet:?xt=urn:btih:abc123');

        $this->assertSame('magnet:?xt=urn:btih:abc123', $request->uri);
        $this->assertNull($request->metainfoBase64);
        $this->assertFalse($request->startPaused);
    }

    public function testFromUriAcceptsHttpUrl(): void
    {
        $request = AddTorrentRequest::fromUri('https://example.com/file.torrent');

        $this->assertSame('https://example.com/file.torrent', $request->uri);
    }

    public function testFromUriDefaultsToStartingImmediately(): void
    {
        $request = AddTorrentRequest::fromUri('magnet:?xt=urn:btih:abc123');

        $this->assertFalse($request->startPaused);
    }

    public function testFromUriCanRequestStartingPaused(): void
    {
        $request = AddTorrentRequest::fromUri('magnet:?xt=urn:btih:abc123', true);

        $this->assertTrue($request->startPaused);
    }

    public function testFromUriRejectsEmptyValue(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        AddTorrentRequest::fromUri('');
    }

    public function testFromUriRejectsLocalFilePaths(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        AddTorrentRequest::fromUri('/etc/passwd');
    }

    public function testFromUriRejectsFileScheme(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        AddTorrentRequest::fromUri('file:///etc/passwd');
    }

    public function testFromTorrentFileContentAcceptsBencodedData(): void
    {
        $request = AddTorrentRequest::fromTorrentFileContent('d8:announce...e');

        $this->assertSame(base64_encode('d8:announce...e'), $request->metainfoBase64);
        $this->assertNull($request->uri);
        $this->assertFalse($request->startPaused);
    }

    public function testFromTorrentFileContentCanRequestStartingPaused(): void
    {
        $request = AddTorrentRequest::fromTorrentFileContent('d8:announce...e', true);

        $this->assertTrue($request->startPaused);
    }

    public function testFromTorrentFileContentRejectsEmptyContent(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        AddTorrentRequest::fromTorrentFileContent('');
    }

    public function testFromTorrentFileContentRejectsNonBencodedData(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        AddTorrentRequest::fromTorrentFileContent('not a torrent file');
    }
}
