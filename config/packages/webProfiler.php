<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'when@dev' => [
        'web_profiler' => [
            'toolbar' => true,
        ],
        'framework' => [
            'profiler' => [
                'enabled' => true,
                'collect' => true,
            ],
        ],
        'debug' => [
            'dump_destination' => 'tcp://%env(VAR_DUMPER_SERVER)%',
        ],
    ],
    'when@test' => [
        'framework' => [
            'profiler' => [
                'collect' => false,
            ],
        ],
    ],
]);
