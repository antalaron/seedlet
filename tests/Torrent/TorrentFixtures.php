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

/**
 * Builds raw Transmission "torrent-get" RPC payloads for use in tests.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TorrentFixtures
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function listPayload(int $id = 1, array $overrides = []): array
    {
        return [
            'id' => $id,
            'name' => 'ubuntu-24.04.iso',
            'status' => 4,
            'percentDone' => 0.5,
            'sizeWhenDone' => 2_000_000_000,
            'totalSize' => 2_000_000_000,
            'leftUntilDone' => 1_000_000_000,
            'downloadedEver' => 1_000_000_000,
            'rateDownload' => 512_000,
            'rateUpload' => 128_000,
            'eta' => 3600,
            'queuePosition' => 0,
            'isFinished' => false,
            'isStalled' => false,
            'errorString' => '',
            'addedDate' => 1_700_000_000,
            'trackerStats' => [
                ['seederCount' => 10, 'leecherCount' => 3],
                ['seederCount' => -1, 'leecherCount' => -1],
            ],
            ...$overrides,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function detailPayload(int $id = 1, array $overrides = []): array
    {
        return [
            ...self::listPayload($id),
            'uploadedEver' => 500_000_000,
            'uploadRatio' => 0.5,
            'downloadDir' => '/downloads',
            'doneDate' => 0,
            'peer-limit' => 50,
            'seedRatioLimit' => 2.0,
            'seedRatioMode' => 0,
            'seedIdleLimit' => 30,
            'seedIdleMode' => 0,
            'bandwidthPriority' => 0,
            'downloadLimited' => false,
            'downloadLimit' => 0,
            'uploadLimited' => false,
            'uploadLimit' => 0,
            'files' => [
                ['name' => 'ubuntu-24.04.iso', 'length' => 2_000_000_000, 'bytesCompleted' => 1_000_000_000],
            ],
            'fileStats' => [
                ['wanted' => true, 'priority' => 0],
            ],
            'peers' => [
                [
                    'address' => '203.0.113.1',
                    'clientName' => 'Transmission 4.0.5',
                    'progress' => 0.9,
                    'rateToClient' => 1000,
                    'rateToPeer' => 0,
                    'isDownloadingFrom' => true,
                    'isUploadingTo' => false,
                ],
            ],
            ...$overrides,
        ];
    }
}
