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
 * A validated, partial update of a torrent's file wanted/priority state.
 *
 * Every property is `null` when not part of the request.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class FileSelectionRequest
{
    /**
     * @param list<int>|null $wanted
     * @param list<int>|null $unwanted
     * @param list<int>|null $priorityHigh
     * @param list<int>|null $priorityNormal
     * @param list<int>|null $priorityLow
     */
    public function __construct(
        public ?array $wanted = null,
        public ?array $unwanted = null,
        public ?array $priorityHigh = null,
        public ?array $priorityNormal = null,
        public ?array $priorityLow = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data decoded JSON request body
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wanted: self::optionalIndexList($data, 'wanted'),
            unwanted: self::optionalIndexList($data, 'unwanted'),
            priorityHigh: self::optionalIndexList($data, 'priorityHigh'),
            priorityNormal: self::optionalIndexList($data, 'priorityNormal'),
            priorityLow: self::optionalIndexList($data, 'priorityLow'),
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<int>|null
     */
    private static function optionalIndexList(array $data, string $key): ?array
    {
        if (!\array_key_exists($key, $data) || null === $data[$key]) {
            return null;
        }

        if (!\is_array($data[$key])) {
            throw new InvalidTorrentRequestException(\sprintf('"%s" must be a list of file indices.', $key));
        }

        $indices = [];
        foreach ($data[$key] as $index) {
            if (!is_numeric($index) || (int) $index < 0) {
                throw new InvalidTorrentRequestException(\sprintf('"%s" must only contain non-negative file indices.', $key));
            }

            $indices[] = (int) $index;
        }

        return $indices;
    }
}
