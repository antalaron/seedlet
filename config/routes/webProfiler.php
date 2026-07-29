<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader\Configurator;

return Routes::config([
    'when@dev' => [
        'web_profiler_wdt' => [
            'resource' => '@WebProfilerBundle/Resources/config/routing/wdt.php',
            'prefix' => '/_wdt',
        ],
        'web_profiler_profiler' => [
            'resource' => '@WebProfilerBundle/Resources/config/routing/profiler.php',
            'prefix' => '/_profiler',
        ],
    ],
]);
