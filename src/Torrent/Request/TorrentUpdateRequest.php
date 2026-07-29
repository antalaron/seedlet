<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Torrent\Request;

use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;

/**
 * A validated, partial update of a torrent's settings.
 *
 * Every property is nullable: `null` means "leave this setting unchanged".
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class TorrentUpdateRequest
{
    private const VALID_PRIORITIES = [-1, 0, 1];
    private const VALID_MODES = [0, 1, 2];

    public function __construct(
        public ?int $bandwidthPriority = null,
        public ?string $downloadDir = null,
        public ?float $seedRatioLimit = null,
        public ?int $seedRatioMode = null,
        public ?int $seedIdleLimit = null,
        public ?int $seedIdleMode = null,
        public ?int $peerLimit = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data decoded JSON request body
     */
    public static function fromArray(array $data): self
    {
        $priority = self::optionalInt($data, 'priority');
        if (null !== $priority && !\in_array($priority, self::VALID_PRIORITIES, true)) {
            throw new InvalidTorrentRequestException('Priority must be -1 (low), 0 (normal) or 1 (high).');
        }

        $seedRatioMode = self::optionalInt($data, 'seedRatioMode');
        if (null !== $seedRatioMode && !\in_array($seedRatioMode, self::VALID_MODES, true)) {
            throw new InvalidTorrentRequestException('Seed ratio mode must be 0 (global), 1 (custom) or 2 (unlimited).');
        }

        $seedIdleMode = self::optionalInt($data, 'seedIdleMode');
        if (null !== $seedIdleMode && !\in_array($seedIdleMode, self::VALID_MODES, true)) {
            throw new InvalidTorrentRequestException('Seed idle mode must be 0 (global), 1 (custom) or 2 (unlimited).');
        }

        $seedRatioLimit = self::optionalFloat($data, 'seedRatioLimit');
        if (null !== $seedRatioLimit && 0 > $seedRatioLimit) {
            throw new InvalidTorrentRequestException('Seed ratio limit cannot be negative.');
        }

        $seedIdleLimit = self::optionalInt($data, 'seedIdleLimit');
        if (null !== $seedIdleLimit && 0 > $seedIdleLimit) {
            throw new InvalidTorrentRequestException('Seed idle limit cannot be negative.');
        }

        $peerLimit = self::optionalInt($data, 'peerLimit');
        if (null !== $peerLimit && 1 > $peerLimit) {
            throw new InvalidTorrentRequestException('Peer limit must be at least 1.');
        }

        $downloadDir = null;
        if (\array_key_exists('downloadDir', $data) && null !== $data['downloadDir']) {
            $downloadDir = trim((string) $data['downloadDir']);

            if ('' === $downloadDir) {
                throw new InvalidTorrentRequestException('Download directory cannot be empty.');
            }
        }

        return new self(
            bandwidthPriority: $priority,
            downloadDir: $downloadDir,
            seedRatioLimit: $seedRatioLimit,
            seedRatioMode: $seedRatioMode,
            seedIdleLimit: $seedIdleLimit,
            seedIdleMode: $seedIdleMode,
            peerLimit: $peerLimit,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optionalInt(array $data, string $key): ?int
    {
        if (!\array_key_exists($key, $data) || null === $data[$key]) {
            return null;
        }

        if (!is_numeric($data[$key])) {
            throw new InvalidTorrentRequestException(\sprintf('"%s" must be a number.', $key));
        }

        return (int) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optionalFloat(array $data, string $key): ?float
    {
        if (!\array_key_exists($key, $data) || null === $data[$key]) {
            return null;
        }

        if (!is_numeric($data[$key])) {
            throw new InvalidTorrentRequestException(\sprintf('"%s" must be a number.', $key));
        }

        return (float) $data[$key];
    }
}
