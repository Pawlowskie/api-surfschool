<?php

use Symfony\Component\Dotenv\Dotenv;

$autoload = dirname(__DIR__).'/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
