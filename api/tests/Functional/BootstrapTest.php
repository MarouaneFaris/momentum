<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BootstrapTest extends KernelTestCase
{
    public function testKernelBoots(): void
    {
        self::bootKernel();

        self::assertNotNull(self::$kernel);
    }
}
