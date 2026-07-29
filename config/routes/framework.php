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
        '_errors' => [
            'resource' => '@FrameworkBundle/Resources/config/routing/errors.php',
            'prefix' => '/_error',
        ],
    ],
]);
