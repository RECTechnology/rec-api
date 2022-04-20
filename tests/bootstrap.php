<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}


$dbpath = dirname(__DIR__) . '/var/cache/test/mongo';
$logfile = dirname(__DIR__) . '/var/logs/mongo-test.log';
$cmd = "mkdir -p $dbpath && mongod --dbpath $dbpath 2>&1 >> $logfile; rm -rf $dbpath";
$mongo = new Process($cmd);
$mongo->start();
