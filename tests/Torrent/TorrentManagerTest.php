<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests\Torrent;

use Antalaron\Seedlet\Torrent\Exception\TorrentNotFoundException;
use Antalaron\Seedlet\Torrent\Request\AddTorrentRequest;
use Antalaron\Seedlet\Torrent\Request\FileSelectionRequest;
use Antalaron\Seedlet\Torrent\Request\SessionUpdateRequest;
use Antalaron\Seedlet\Torrent\Request\TorrentUpdateRequest;
use Antalaron\Seedlet\Torrent\TorrentManager;
use Antalaron\Seedlet\Transmission\TransmissionClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TorrentManagerTest extends TestCase
{
    public function testListTorrentsReturnsSummaries(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-get', $this->callback(static fn (array $arguments): bool => \is_array($arguments['fields'])))
            ->willReturn(['torrents' => [TorrentFixtures::listPayload(1), TorrentFixtures::listPayload(2)]])
        ;

        $torrents = (new TorrentManager($client))->listTorrents();

        $this->assertCount(2, $torrents);
        $this->assertSame(1, $torrents[0]->id);
        $this->assertSame(10, $torrents[0]->seeders);
        $this->assertSame(3, $torrents[0]->leechers);
    }

    public function testGetTorrentReturnsDetail(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn(['torrents' => [TorrentFixtures::detailPayload(42)]]);

        $torrent = (new TorrentManager($client))->getTorrent(42);

        $this->assertSame(42, $torrent->summary->id);
        $this->assertSame('/downloads', $torrent->downloadDir);
        $this->assertCount(1, $torrent->files);
        $this->assertCount(1, $torrent->peers);
    }

    public function testGetTorrentThrowsWhenMissing(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn(['torrents' => []]);

        $this->expectException(TorrentNotFoundException::class);

        (new TorrentManager($client))->getTorrent(99);
    }

    public function testAddTorrentFromMagnetUsesFilenameArgument(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['filename' => 'magnet:?xt=urn:btih:abc'])
            ->willReturn(['torrent-added' => ['id' => 7, 'name' => 'added-torrent']])
        ;

        $added = (new TorrentManager($client))->addTorrent(AddTorrentRequest::fromUri('magnet:?xt=urn:btih:abc'));

        $this->assertSame(7, $added->id);
        $this->assertFalse($added->duplicate);
    }

    public function testAddTorrentFromUrlUsesFilenameArgument(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['filename' => 'https://example.com/file.torrent'])
            ->willReturn(['torrent-added' => ['id' => 8, 'name' => 'from-url']])
        ;

        $added = (new TorrentManager($client))->addTorrent(AddTorrentRequest::fromUri('https://example.com/file.torrent'));

        $this->assertSame(8, $added->id);
    }

    public function testAddTorrentFromFileUsesMetainfoArgument(): void
    {
        $content = 'd8:announce...e';
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['metainfo' => base64_encode($content)])
            ->willReturn(['torrent-added' => ['id' => 9, 'name' => 'from-file']])
        ;

        $added = (new TorrentManager($client))->addTorrent(AddTorrentRequest::fromTorrentFileContent($content));

        $this->assertSame(9, $added->id);
    }

    public function testAddTorrentReportsDuplicate(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn(['torrent-duplicate' => ['id' => 1, 'name' => 'existing']]);

        $added = (new TorrentManager($client))->addTorrent(AddTorrentRequest::fromUri('magnet:?xt=urn:btih:abc'));

        $this->assertTrue($added->duplicate);
    }

    public function testPauseTorrentCallsTorrentStop(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())->method('request')->with('torrent-stop', ['ids' => [5]])->willReturn([]);

        (new TorrentManager($client))->pauseTorrent(5);
    }

    public function testResumeTorrentCallsTorrentStart(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())->method('request')->with('torrent-start', ['ids' => [5]])->willReturn([]);

        (new TorrentManager($client))->resumeTorrent(5);
    }

    public function testRemoveTorrentWithoutDeletingLocalData(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-remove', ['ids' => [5], 'delete-local-data' => false])
            ->willReturn([])
        ;

        (new TorrentManager($client))->removeTorrent(5, false);
    }

    public function testRemoveTorrentWithDeletingLocalData(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-remove', ['ids' => [5], 'delete-local-data' => true])
            ->willReturn([])
        ;

        (new TorrentManager($client))->removeTorrent(5, true);
    }

    public function testUpdateTorrentSendsOnlyProvidedSettings(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-set', ['ids' => [5], 'bandwidthPriority' => 1, 'peer-limit' => 25])
            ->willReturn([])
        ;

        (new TorrentManager($client))->updateTorrent(5, new TorrentUpdateRequest(bandwidthPriority: 1, peerLimit: 25));
    }

    public function testUpdateTorrentDownloadDirMovesData(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-set-location', ['ids' => [5], 'location' => '/new/path', 'move' => true])
            ->willReturn([])
        ;

        (new TorrentManager($client))->updateTorrent(5, new TorrentUpdateRequest(downloadDir: '/new/path'));
    }

    public function testUpdateTorrentSeedingSettings(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-set', [
                'ids' => [5],
                'seedRatioLimit' => 1.5,
                'seedRatioMode' => 1,
                'seedIdleLimit' => 60,
                'seedIdleMode' => 1,
            ])
            ->willReturn([])
        ;

        (new TorrentManager($client))->updateTorrent(5, new TorrentUpdateRequest(
            seedRatioLimit: 1.5,
            seedRatioMode: 1,
            seedIdleLimit: 60,
            seedIdleMode: 1,
        ));
    }

    public function testUpdateFilesSendsWantedAndPriorities(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-set', [
                'ids' => [5],
                'files-wanted' => [0, 1],
                'files-unwanted' => [2],
                'priority-high' => [0],
                'priority-low' => [2],
            ])
            ->willReturn([])
        ;

        (new TorrentManager($client))->updateFiles(5, new FileSelectionRequest(
            wanted: [0, 1],
            unwanted: [2],
            priorityHigh: [0],
            priorityLow: [2],
        ));
    }

    public function testUpdateFilesDoesNothingWhenEmpty(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->never())->method('request');

        (new TorrentManager($client))->updateFiles(5, new FileSelectionRequest());
    }

    public function testGetSessionSettings(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())->method('request')->with('session-get')->willReturn([
            'speed-limit-down-enabled' => true,
            'speed-limit-down' => 500,
            'speed-limit-up-enabled' => false,
            'speed-limit-up' => 100,
            'alt-speed-enabled' => false,
            'alt-speed-down' => 50,
            'alt-speed-up' => 10,
        ]);

        $session = (new TorrentManager($client))->getSessionSettings();

        $this->assertTrue($session->speedLimitDownEnabled);
        $this->assertSame(500, $session->speedLimitDown);
    }

    public function testUpdateSessionSettingsSpeedLimits(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('session-set', ['speed-limit-down-enabled' => true, 'speed-limit-down' => 200])
            ->willReturn([])
        ;

        (new TorrentManager($client))->updateSessionSettings(new SessionUpdateRequest(
            speedLimitDownEnabled: true,
            speedLimitDown: 200,
        ));
    }

    public function testUpdateSessionSettingsTurtleMode(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('session-set', ['alt-speed-enabled' => true])
            ->willReturn([])
        ;

        (new TorrentManager($client))->updateSessionSettings(new SessionUpdateRequest(altSpeedEnabled: true));
    }
}
