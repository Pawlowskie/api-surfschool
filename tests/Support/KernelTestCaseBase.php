<?php

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class KernelTestCaseBase extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        require_once \dirname(__DIR__, 2).'/src/Kernel.php';

        return \App\Kernel::class;
    }
}
