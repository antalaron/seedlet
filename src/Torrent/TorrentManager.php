<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Torrent;

use Antalaron\Seedlet\Torrent\Dto\AddedTorrent;
use Antalaron\Seedlet\Torrent\Dto\SessionSettings;
use Antalaron\Seedlet\Torrent\Dto\TorrentDetail;
use Antalaron\Seedlet\Torrent\Dto\TorrentSummary;
use Antalaron\Seedlet\Torrent\Exception\TorrentNotFoundException;
use Antalaron\Seedlet\Torrent\Request\AddTorrentRequest;
use Antalaron\Seedlet\Torrent\Request\FileSelectionRequest;
use Antalaron\Seedlet\Torrent\Request\SessionUpdateRequest;
use Antalaron\Seedlet\Torrent\Request\TorrentUpdateRequest;
use Antalaron\Seedlet\Transmission\TransmissionClientInterface;

/**
 * Application-level API used by the controllers to drive Transmission.
 *
 * This is the only place in the application that knows about Transmission's
 * RPC method and field names.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class TorrentManager
{
    private const LIST_FIELDS = [
        'id', 'name', 'status', 'percentDone', 'sizeWhenDone', 'totalSize',
        'leftUntilDone', 'downloadedEver', 'uploadedEver', 'rateDownload', 'rateUpload', 'eta',
        'queuePosition', 'isFinished', 'isStalled', 'errorString', 'addedDate',
        'trackerStats',
    ];

    private const DETAIL_FIELDS = [
        ...self::LIST_FIELDS,
        'uploadRatio', 'downloadDir', 'doneDate', 'peer-limit',
        'seedRatioLimit', 'seedRatioMode', 'seedIdleLimit', 'seedIdleMode',
        'bandwidthPriority', 'downloadLimited', 'downloadLimit', 'uploadLimited',
        'uploadLimit', 'files', 'fileStats', 'peers',
    ];

    public function __construct(
        private TransmissionClientInterface $client,
    ) {
    }

    /**
     * @return list<TorrentSummary>
     */
    public function listTorrents(): array
    {
        $result = $this->client->request('torrent-get', ['fields' => self::LIST_FIELDS]);

        return array_values(array_map(
            TorrentSummary::fromRpc(...),
            $result['torrents'] ?? [],
        ));
    }

    public function getTorrent(int $id): TorrentDetail
    {
        $result = $this->client->request('torrent-get', ['ids' => [$id], 'fields' => self::DETAIL_FIELDS]);

        $torrent = $result['torrents'][0] ?? null;

        if (null === $torrent) {
            throw new TorrentNotFoundException($id);
        }

        return TorrentDetail::fromRpc($torrent);
    }

    public function addTorrent(AddTorrentRequest $request): AddedTorrent
    {
        $arguments = null !== $request->metainfoBase64
            ? ['metainfo' => $request->metainfoBase64]
            : ['filename' => $request->uri];

        // Ask Transmission to create the torrent directly in the requested
        // initial state, rather than adding it and issuing a separate
        // torrent-stop call afterwards.
        $arguments['paused'] = $request->startPaused;

        $result = $this->client->request('torrent-add', $arguments);

        $duplicate = isset($result['torrent-duplicate']);
        $added = $result['torrent-added'] ?? $result['torrent-duplicate'];

        return new AddedTorrent(
            id: (int) $added['id'],
            name: (string) $added['name'],
            duplicate: $duplicate,
        );
    }

    public function pauseTorrent(int $id): void
    {
        $this->client->request('torrent-stop', ['ids' => [$id]]);
    }

    public function resumeTorrent(int $id): void
    {
        $this->client->request('torrent-start', ['ids' => [$id]]);
    }

    public function removeTorrent(int $id, bool $deleteLocalData): void
    {
        $this->client->request('torrent-remove', [
            'ids' => [$id],
            'delete-local-data' => $deleteLocalData,
        ]);
    }

    public function updateTorrent(int $id, TorrentUpdateRequest $request): void
    {
        $settings = array_filter([
            'bandwidthPriority' => $request->bandwidthPriority,
            'seedRatioLimit' => $request->seedRatioLimit,
            'seedRatioMode' => $request->seedRatioMode,
            'seedIdleLimit' => $request->seedIdleLimit,
            'seedIdleMode' => $request->seedIdleMode,
            'peer-limit' => $request->peerLimit,
        ], static fn (mixed $value): bool => null !== $value);

        if ([] !== $settings) {
            $this->client->request('torrent-set', ['ids' => [$id], ...$settings]);
        }

        if (null !== $request->downloadDir) {
            $this->client->request('torrent-set-location', [
                'ids' => [$id],
                'location' => $request->downloadDir,
                'move' => true,
            ]);
        }
    }

    public function updateFiles(int $id, FileSelectionRequest $request): void
    {
        // Transmission treats an *empty* files-wanted/-unwanted or
        // priority-* list as "apply to every file in the torrent" rather
        // than "apply to no file", so empty lists must be omitted here just
        // like null ones - otherwise sending e.g. an empty "priority-normal"
        // alongside a non-empty "priority-high" silently resets every file
        // (including the ones just set to high) back to normal priority.
        $settings = array_filter([
            'files-wanted' => $request->wanted,
            'files-unwanted' => $request->unwanted,
            'priority-high' => $request->priorityHigh,
            'priority-normal' => $request->priorityNormal,
            'priority-low' => $request->priorityLow,
        ], static fn (?array $value): bool => null !== $value && [] !== $value);

        if ([] === $settings) {
            return;
        }

        $this->client->request('torrent-set', ['ids' => [$id], ...$settings]);
    }

    public function getSessionSettings(): SessionSettings
    {
        $result = $this->client->request('session-get');

        return SessionSettings::fromRpc($result);
    }

    public function updateSessionSettings(SessionUpdateRequest $request): void
    {
        $settings = array_filter([
            'speed-limit-down-enabled' => $request->speedLimitDownEnabled,
            'speed-limit-down' => $request->speedLimitDown,
            'speed-limit-up-enabled' => $request->speedLimitUpEnabled,
            'speed-limit-up' => $request->speedLimitUp,
            'alt-speed-enabled' => $request->altSpeedEnabled,
        ], static fn (mixed $value): bool => null !== $value);

        if ([] === $settings) {
            return;
        }

        $this->client->request('session-set', $settings);
    }
}
