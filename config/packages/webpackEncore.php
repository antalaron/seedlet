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
    'webpack_encore' => [
        'output_path' => '%kernel.project_dir%/public/build',
        'script_attributes' => [
            'defer' => true,
        ],
    ],
    'framework' => [
        'assets' => [
            'json_manifest_path' => '%kernel.project_dir%/public/build/manifest.json',
        ],
    ],
]);
