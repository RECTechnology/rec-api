<?php

use Symfony\Component\Process\Process;

require dirname(__DIR__).'/config/bootstrap.php';

$logfile = dirname(__DIR__) . '/var/log/mongodb-server.log';

$test_mongo_path = dirname(__DIR__) . '/var/cache/test/mongo';
$test_sqlite_path = dirname(__DIR__) . '/var/cache/test/rdb';

// creating directories for testing
$cmd = "mkdir -p $test_mongo_path ; mkdir -p $test_sqlite_path";
$prepare_test_process = Process::fromShellCommandline($cmd);
$prepare_test_process->run();

// start mongo
$cmd = "mongod --dbpath $test_mongo_path 2>&1 >> $logfile; rm -rf $test_mongo_path/*";
$prepare_test_process = Process::fromShellCommandline($cmd);
$prepare_test_process->start();
