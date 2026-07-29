<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Antalaron\Seedlet\Kernel;

require_once dirname(__DIR__).'/config/bootstrap.php';

return static function (array $context): Kernel {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
