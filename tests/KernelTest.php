<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Tests;

use Antalaron\Seedlet\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class KernelTest extends TestCase
{
    public function testKernelBoots(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        $reflection = new \ReflectionClass($kernel);
        $property = $reflection->getProperty('booted');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($kernel));
    }
}
