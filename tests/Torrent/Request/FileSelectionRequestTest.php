<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests\Torrent\Request;

use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;
use Antalaron\Seedlet\Torrent\Request\FileSelectionRequest;
use PHPUnit\Framework\TestCase;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class FileSelectionRequestTest extends TestCase
{
    public function testFromArrayMapsProvidedIndices(): void
    {
        $request = FileSelectionRequest::fromArray([
            'wanted' => [0, 1],
            'unwanted' => [2],
            'priorityHigh' => ['3'],
        ]);

        $this->assertSame([0, 1], $request->wanted);
        $this->assertSame([2], $request->unwanted);
        $this->assertSame([3], $request->priorityHigh);
        $this->assertNull($request->priorityNormal);
    }

    public function testFromArrayRejectsNonArrayValue(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        FileSelectionRequest::fromArray(['wanted' => 'not-an-array']);
    }

    public function testFromArrayRejectsNegativeIndex(): void
    {
        $this->expectException(InvalidTorrentRequestException::class);

        FileSelectionRequest::fromArray(['wanted' => [-1]]);
    }
}
