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
 * A validated, partial update of Transmission's global session settings.
 *
 * Every property is `null` when not part of the request.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class SessionUpdateRequest
{
    public function __construct(
        public ?bool $speedLimitDownEnabled = null,
        public ?int $speedLimitDown = null,
        public ?bool $speedLimitUpEnabled = null,
        public ?int $speedLimitUp = null,
        public ?bool $altSpeedEnabled = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data decoded JSON request body
     */
    public static function fromArray(array $data): self
    {
        $speedLimitDown = self::optionalNonNegativeInt($data, 'speedLimitDown');
        $speedLimitUp = self::optionalNonNegativeInt($data, 'speedLimitUp');

        return new self(
            speedLimitDownEnabled: self::optionalBool($data, 'speedLimitDownEnabled'),
            speedLimitDown: $speedLimitDown,
            speedLimitUpEnabled: self::optionalBool($data, 'speedLimitUpEnabled'),
            speedLimitUp: $speedLimitUp,
            altSpeedEnabled: self::optionalBool($data, 'altSpeedEnabled'),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optionalBool(array $data, string $key): ?bool
    {
        if (!\array_key_exists($key, $data) || null === $data[$key]) {
            return null;
        }

        if (!\is_bool($data[$key])) {
            throw new InvalidTorrentRequestException(\sprintf('"%s" must be a boolean.', $key));
        }

        return $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function optionalNonNegativeInt(array $data, string $key): ?int
    {
        if (!\array_key_exists($key, $data) || null === $data[$key]) {
            return null;
        }

        if (!is_numeric($data[$key]) || (int) $data[$key] < 0) {
            throw new InvalidTorrentRequestException(\sprintf('"%s" must be a non-negative number.', $key));
        }

        return (int) $data[$key];
    }
}
