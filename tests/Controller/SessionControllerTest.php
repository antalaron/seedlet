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

use Antalaron\Seedlet\Controller\SessionController;
use Antalaron\Seedlet\Torrent\TorrentManager;
use Antalaron\Seedlet\Transmission\TransmissionClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class SessionControllerTest extends TestCase
{
    public function testGetReturnsSessionSettings(): void
    {
        $client = $this->createStub(TransmissionClientInterface::class);
        $client->method('request')->willReturn([
            'speed-limit-down-enabled' => true,
            'speed-limit-down' => 500,
            'speed-limit-up-enabled' => false,
            'speed-limit-up' => 100,
            'alt-speed-enabled' => true,
            'alt-speed-down' => 50,
            'alt-speed-up' => 10,
        ]);

        $response = $this->controller($client)->get();

        $data = $this->decode($response);
        $this->assertTrue($data['session']['speedLimitDownEnabled']);
        $this->assertTrue($data['session']['altSpeedEnabled']);
    }

    public function testUpdateTogglesTurtleMode(): void
    {
        $client = $this->createMock(TransmissionClientInterface::class);
        $client->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, array $arguments = []) {
                if ('session-set' === $method) {
                    $this->assertSame(['alt-speed-enabled' => true], $arguments);

                    return [];
                }

                return [
                    'speed-limit-down-enabled' => false,
                    'speed-limit-down' => 0,
                    'speed-limit-up-enabled' => false,
                    'speed-limit-up' => 0,
                    'alt-speed-enabled' => true,
                    'alt-speed-down' => 50,
                    'alt-speed-up' => 10,
                ];
            })
        ;

        $request = Request::create('/api/session', 'PATCH', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['altSpeedEnabled' => true]));

        $response = $this->controller($client)->update($request);

        $this->assertTrue($this->decode($response)['session']['altSpeedEnabled']);
    }

    private function controller(TransmissionClientInterface $client): SessionController
    {
        $controller = new SessionController(new TorrentManager($client));
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
