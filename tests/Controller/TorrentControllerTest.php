<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests\Controller;

use Antalaron\Seedlet\Controller\TorrentController;
use Antalaron\Seedlet\Tests\Torrent\TorrentFixtures;
use Antalaron\Seedlet\Torrent\Exception\InvalidTorrentRequestException;
use Antalaron\Seedlet\Torrent\Exception\TorrentNotFoundException;
use Antalaron\Seedlet\Torrent\TorrentManager;
use Antalaron\Seedlet\Transmission\TransmissionClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class TorrentControllerTest extends TestCase
{
    public function testListReturnsAllTorrents(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn(['torrents' => [TorrentFixtures::listPayload(1)]]);

        $response = $this->controller($client)->list();

        $data = $this->decode($response);
        $this->assertCount(1, $data['torrents']);
    }

    public function testGetReturnsTorrentDetail(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn(['torrents' => [TorrentFixtures::detailPayload(1)]]);

        $response = $this->controller($client)->get(1);

        $data = $this->decode($response);
        $this->assertSame(1, $data['torrent']['id']);
    }

    public function testGetThrowsNotFoundForMissingTorrent(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn(['torrents' => []]);

        $this->expectException(TorrentNotFoundException::class);

        $this->controller($client)->get(1);
    }

    public function testAddFromUriPayload(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['filename' => 'magnet:?xt=urn:btih:abc', 'paused' => false])
            ->willReturn(['torrent-added' => ['id' => 3, 'name' => 'added']])
        ;

        $request = Request::create('/api/torrents', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['source' => 'magnet:?xt=urn:btih:abc']));

        $response = $this->controller($client)->add($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(3, $this->decode($response)['torrent']['id']);
    }

    public function testAddFromUriPayloadCanStartPaused(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['filename' => 'magnet:?xt=urn:btih:abc', 'paused' => true])
            ->willReturn(['torrent-added' => ['id' => 4, 'name' => 'added']])
        ;

        $request = Request::create('/api/torrents', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['source' => 'magnet:?xt=urn:btih:abc', 'startPaused' => true]));

        $response = $this->controller($client)->add($request);

        $this->assertSame(4, $this->decode($response)['torrent']['id']);
    }

    public function testAddFromFileUploadDefaultsToStartingImmediately(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['metainfo' => base64_encode('d8:announce...e'), 'paused' => false])
            ->willReturn(['torrent-added' => ['id' => 5, 'name' => 'from-file']])
        ;

        $path = tempnam(sys_get_temp_dir(), 'seedlet-test-');
        file_put_contents($path, 'd8:announce...e');
        $file = new UploadedFile($path, 'test.torrent', test: true);

        $request = Request::create('/api/torrents', 'POST', files: ['file' => $file]);

        $response = $this->controller($client)->add($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(5, $this->decode($response)['torrent']['id']);

        unlink($path);
    }

    public function testAddFromFileUploadCanStartPaused(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-add', ['metainfo' => base64_encode('d8:announce...e'), 'paused' => true])
            ->willReturn(['torrent-added' => ['id' => 6, 'name' => 'from-file']])
        ;

        $path = tempnam(sys_get_temp_dir(), 'seedlet-test-');
        file_put_contents($path, 'd8:announce...e');
        $file = new UploadedFile($path, 'test.torrent', test: true);

        $request = Request::create('/api/torrents', 'POST', parameters: ['startPaused' => 'true'], files: ['file' => $file]);

        $response = $this->controller($client)->add($request);

        $this->assertSame(6, $this->decode($response)['torrent']['id']);

        unlink($path);
    }

    public function testAddRejectsInvalidSource(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $request = Request::create('/api/torrents', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['source' => '/etc/passwd']));

        $this->expectException(InvalidTorrentRequestException::class);

        $this->controller($client)->add($request);
    }

    public function testPauseCallsTorrentManager(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())->method('request')->with('torrent-stop', ['ids' => [4]])->willReturn([]);

        $response = $this->controller($client)->pause(4);

        $this->assertSame(['status' => 'ok'], $this->decode($response));
    }

    public function testResumeCallsTorrentManager(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())->method('request')->with('torrent-start', ['ids' => [4]])->willReturn([]);

        $response = $this->controller($client)->resume(4);

        $this->assertSame(['status' => 'ok'], $this->decode($response));
    }

    public function testRemoveWithoutDeletingData(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-remove', ['ids' => [4], 'delete-local-data' => false])
            ->willReturn([])
        ;

        $request = Request::create('/api/torrents/4', 'DELETE', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $this->controller($client)->remove(4, $request);
    }

    public function testRemoveWithDeletingData(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('torrent-remove', ['ids' => [4], 'delete-local-data' => true])
            ->willReturn([])
        ;

        $request = Request::create('/api/torrents/4', 'DELETE', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['deleteLocalData' => true]));

        $this->controller($client)->remove(4, $request);
    }

    public function testRemoveRejectsNonBooleanDeleteFlag(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $request = Request::create('/api/torrents/4', 'DELETE', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['deleteLocalData' => 'yes']));

        $this->expectException(InvalidTorrentRequestException::class);

        $this->controller($client)->remove(4, $request);
    }

    public function testUpdateFilesSendsFileSelection(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, array $arguments = []) {
                if ('torrent-set' === $method) {
                    $this->assertSame(['ids' => [4], 'files-wanted' => [0]], $arguments);

                    return [];
                }

                return ['torrents' => [TorrentFixtures::detailPayload(4)]];
            })
        ;

        $request = Request::create('/api/torrents/4/files', 'PATCH', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['wanted' => [0]]));

        $this->controller($client)->updateFiles(4, $request);
    }

    private function controller(TransmissionClientInterface $client): TorrentController
    {
        $controller = new TorrentController(new TorrentManager($client));
        $controller->setContainer(new Container());

        return $controller;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }
}
