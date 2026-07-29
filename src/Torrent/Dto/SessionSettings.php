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
 * The subset of Transmission's global session settings exposed by the UI.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final readonly class SessionSettings implements \JsonSerializable
{
    public function __construct(
        public bool $speedLimitDownEnabled,
        public int $speedLimitDown,
        public bool $speedLimitUpEnabled,
        public int $speedLimitUp,
        public bool $altSpeedEnabled,
        public int $altSpeedDown,
        public int $altSpeedUp,
    ) {
    }

    /**
     * @param array<string, mixed> $data the session-get RPC "arguments"
     */
    public static function fromRpc(array $data): self
    {
        return new self(
            speedLimitDownEnabled: (bool) $data['speed-limit-down-enabled'],
            speedLimitDown: (int) $data['speed-limit-down'],
            speedLimitUpEnabled: (bool) $data['speed-limit-up-enabled'],
            speedLimitUp: (int) $data['speed-limit-up'],
            altSpeedEnabled: (bool) $data['alt-speed-enabled'],
            altSpeedDown: (int) $data['alt-speed-down'],
            altSpeedUp: (int) $data['alt-speed-up'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'speedLimitDownEnabled' => $this->speedLimitDownEnabled,
            'speedLimitDown' => $this->speedLimitDown,
            'speedLimitUpEnabled' => $this->speedLimitUpEnabled,
            'speedLimitUp' => $this->speedLimitUp,
            'altSpeedEnabled' => $this->altSpeedEnabled,
            'altSpeedDown' => $this->altSpeedDown,
            'altSpeedUp' => $this->altSpeedUp,
        ];
    }
}
