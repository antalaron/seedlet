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
 * The information shown for a torrent in the torrent list.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class TorrentSummary implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public TorrentStatus $status,
        public float $percentDone,
        public int $sizeWhenDone,
        public int $totalSize,
        public int $leftUntilDone,
        public int $downloadedEver,
        public int $rateDownload,
        public int $rateUpload,
        public int $eta,
        public int $queuePosition,
        public bool $isFinished,
        public bool $isStalled,
        public string $errorString,
        public \DateTimeImmutable $addedDate,
        public int $seeders,
        public int $leechers,
    ) {
    }

    /**
     * @param array<string, mixed> $data an entry of the torrent-get "torrents" RPC field
     */
    public static function fromRpc(array $data): self
    {
        [$seeders, $leechers] = self::countPeersFromTrackers($data['trackerStats'] ?? []);

        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            status: TorrentStatus::from((int) $data['status']),
            percentDone: (float) $data['percentDone'],
            sizeWhenDone: (int) $data['sizeWhenDone'],
            totalSize: (int) $data['totalSize'],
            leftUntilDone: (int) $data['leftUntilDone'],
            downloadedEver: (int) $data['downloadedEver'],
            rateDownload: (int) $data['rateDownload'],
            rateUpload: (int) $data['rateUpload'],
            eta: (int) $data['eta'],
            queuePosition: (int) $data['queuePosition'],
            isFinished: (bool) $data['isFinished'],
            isStalled: (bool) $data['isStalled'],
            errorString: (string) $data['errorString'],
            addedDate: (new \DateTimeImmutable())->setTimestamp((int) $data['addedDate']),
            seeders: $seeders,
            leechers: $leechers,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'percentDone' => $this->percentDone,
            'sizeWhenDone' => $this->sizeWhenDone,
            'totalSize' => $this->totalSize,
            'leftUntilDone' => $this->leftUntilDone,
            'downloadedEver' => $this->downloadedEver,
            'rateDownload' => $this->rateDownload,
            'rateUpload' => $this->rateUpload,
            'eta' => $this->eta,
            'queuePosition' => $this->queuePosition,
            'isFinished' => $this->isFinished,
            'isStalled' => $this->isStalled,
            'errorString' => $this->errorString,
            'addedDate' => $this->addedDate->format(\DateTimeInterface::ATOM),
            'seeders' => $this->seeders,
            'leechers' => $this->leechers,
        ];
    }

    /**
     * Transmission does not expose an aggregated swarm seeder/leecher count,
     * so it is derived by summing the per-tracker statistics.
     *
     * @param array<int, array<string, mixed>> $trackerStats the "trackerStats" RPC field
     *
     * @return array{0: int, 1: int}
     */
    private static function countPeersFromTrackers(array $trackerStats): array
    {
        $seeders = 0;
        $leechers = 0;

        foreach ($trackerStats as $tracker) {
            $seeders += max(0, (int) ($tracker['seederCount'] ?? -1));
            $leechers += max(0, (int) ($tracker['leecherCount'] ?? -1));
        }

        return [$seeders, $leechers];
    }
}
