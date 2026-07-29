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
 * The full information shown on the torrent details screen.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class TorrentDetail implements \JsonSerializable
{
    /**
     * @param list<TorrentFile> $files
     * @param list<TorrentPeer> $peers
     */
    public function __construct(
        public TorrentSummary $summary,
        public int $uploadedEver,
        public float $uploadRatio,
        public string $downloadDir,
        public int $doneDate,
        public int $peerLimit,
        public float $seedRatioLimit,
        public int $seedRatioMode,
        public int $seedIdleLimit,
        public int $seedIdleMode,
        public int $bandwidthPriority,
        public bool $downloadLimited,
        public int $downloadLimit,
        public bool $uploadLimited,
        public int $uploadLimit,
        public array $files,
        public array $peers,
    ) {
    }

    /**
     * @param array<string, mixed> $data an entry of the torrent-get "torrents" RPC field
     */
    public static function fromRpc(array $data): self
    {
        $files = [];

        foreach ($data['files'] ?? [] as $index => $file) {
            $files[] = TorrentFile::fromRpc($index, $file, $data['fileStats'][$index] ?? []);
        }

        $peers = array_map(TorrentPeer::fromRpc(...), $data['peers'] ?? []);

        return new self(
            summary: TorrentSummary::fromRpc($data),
            uploadedEver: (int) $data['uploadedEver'],
            uploadRatio: (float) $data['uploadRatio'],
            downloadDir: (string) $data['downloadDir'],
            doneDate: (int) $data['doneDate'],
            peerLimit: (int) $data['peer-limit'],
            seedRatioLimit: (float) $data['seedRatioLimit'],
            seedRatioMode: (int) $data['seedRatioMode'],
            seedIdleLimit: (int) $data['seedIdleLimit'],
            seedIdleMode: (int) $data['seedIdleMode'],
            bandwidthPriority: (int) $data['bandwidthPriority'],
            downloadLimited: (bool) $data['downloadLimited'],
            downloadLimit: (int) $data['downloadLimit'],
            uploadLimited: (bool) $data['uploadLimited'],
            uploadLimit: (int) $data['uploadLimit'],
            files: $files,
            peers: $peers,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ...$this->summary->jsonSerialize(),
            'uploadedEver' => $this->uploadedEver,
            'uploadRatio' => $this->uploadRatio,
            'downloadDir' => $this->downloadDir,
            'doneDate' => 0 < $this->doneDate ? (new \DateTimeImmutable())->setTimestamp($this->doneDate)->format(\DateTimeInterface::ATOM) : null,
            'peerLimit' => $this->peerLimit,
            'seedRatioLimit' => $this->seedRatioLimit,
            'seedRatioMode' => $this->seedRatioMode,
            'seedIdleLimit' => $this->seedIdleLimit,
            'seedIdleMode' => $this->seedIdleMode,
            'bandwidthPriority' => $this->bandwidthPriority,
            'downloadLimited' => $this->downloadLimited,
            'downloadLimit' => $this->downloadLimit,
            'uploadLimited' => $this->uploadLimited,
            'uploadLimit' => $this->uploadLimit,
            'files' => array_map(static fn (TorrentFile $file): array => [
                'index' => $file->index,
                'name' => $file->name,
                'length' => $file->length,
                'bytesCompleted' => $file->bytesCompleted,
                'wanted' => $file->wanted,
                'priority' => $file->priority,
            ], $this->files),
            'peers' => array_map(static fn (TorrentPeer $peer): array => [
                'address' => $peer->address,
                'clientName' => $peer->clientName,
                'progress' => $peer->progress,
                'rateToClient' => $peer->rateToClient,
                'rateToPeer' => $peer->rateToPeer,
                'isDownloadingFrom' => $peer->isDownloadingFrom,
                'isUploadingTo' => $peer->isUploadingTo,
            ], $this->peers),
        ];
    }
}
