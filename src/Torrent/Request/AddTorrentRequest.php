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
 * A validated request to add a torrent, either from a magnet link / URL or
 * from the raw content of an uploaded ".torrent" file.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class AddTorrentRequest
{
    private const MAX_TORRENT_FILE_SIZE = 10 * 1024 * 1024;

    private function __construct(
        public ?string $uri,
        public ?string $metainfoBase64,
    ) {
    }

    public static function fromUri(string $uri): self
    {
        $uri = trim($uri);

        if ('' === $uri) {
            throw new InvalidTorrentRequestException('A magnet link or torrent URL is required.');
        }

        if (str_starts_with($uri, 'magnet:?')) {
            return new self($uri, null);
        }

        $scheme = strtolower((string) parse_url($uri, \PHP_URL_SCHEME));

        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidTorrentRequestException('Only magnet links and http(s) torrent URLs are supported.');
        }

        return new self($uri, null);
    }

    public static function fromTorrentFileContent(string $content): self
    {
        if ('' === $content) {
            throw new InvalidTorrentRequestException('The uploaded torrent file is empty.');
        }

        if (\strlen($content) > self::MAX_TORRENT_FILE_SIZE) {
            throw new InvalidTorrentRequestException('The uploaded torrent file is too large.');
        }

        if (!str_starts_with($content, 'd')) {
            throw new InvalidTorrentRequestException('The uploaded file is not a valid torrent file.');
        }

        return new self(null, base64_encode($content));
    }
}
