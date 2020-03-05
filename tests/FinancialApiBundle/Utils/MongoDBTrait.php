<?php

namespace Test\FinancialApiBundle\Utils;

use Symfony\Component\Process\Process;

trait MongoDBTrait {

    /** @var Process $mongoProcess */
    protected static $mongoProcess;
    protected static $dbPath = 'var/db/mongo';

    /**
     * @param array $options
     * @return \Symfony\Component\HttpKernel\KernelInterface
     */
    protected static function bootKernel(array $options = [])
    {
        $kernel = parent::bootKernel($options);
        $gracePeriod = 1;
        $absolutePath = self::getDBPath();
        if(!file_exists($absolutePath)) mkdir($absolutePath);
        static::$mongoProcess = new Process("mongod --dbpath $absolutePath");
        static::$mongoProcess->start();
        sleep($gracePeriod);
        return $kernel;
    }

    /**
     * @return string
     */
    private static function getDBPath(){
        return self::$kernel->getContainer()->getParameter('kernel.project_dir') . '/' . self::$dbPath;
    }

    /**
     * Shuts the kernel down if it was used in the test - called by the tearDown method by default.
     */
    protected static function ensureKernelShutdown()
    {
        $absolutePath = self::getDBPath();
        parent::ensureKernelShutdown();

        if(self::$mongoProcess != null && !self::$mongoProcess->isRunning()) {
            self::$mongoProcess->stop();
        }
        if(!file_exists($absolutePath)) unlink($absolutePath);
    }




}