<?php

namespace App\Tests\Support;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

abstract class ApiTestCaseBase extends ApiTestCase
{
    protected static function getKernelClass(): string
    {
        require_once \dirname(__DIR__, 2).'/src/Kernel.php';

        return \App\Kernel::class;
    }
}
