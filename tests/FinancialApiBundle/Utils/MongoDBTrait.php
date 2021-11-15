<?php

namespace Test\FinancialApiBundle\Utils;

use App\FinancialApiBundle\Exception\MongoTimeoutException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

trait MongoDBTrait {

    /** @var Process $mongoProcess */
    protected static $mongoProcess;
    protected static $dbPath = 'var/db/mongo';
    protected static $connectionTimeout = 10;
    protected static $connectionRetries = 3;

    public function startMongo(): void {
        $absolutePath = self::getDBPath();
        if(!file_exists($absolutePath)) mkdir($absolutePath);
        for($i=0; $i<self::$connectionRetries; $i++) {
            try {
                self::$mongoProcess = new Process("mongod --dbpath $absolutePath");
                self::$mongoProcess->start();
                $dm = self::$kernel->getContainer()->get('doctrine.odm.mongodb.document_manager');
                $startConnectTime = time();
                while (true) {
                    if (time() - $startConnectTime > self::$connectionTimeout)
                        throw new MongoTimeoutException("Mongodb server lasted too much to connect");
                    try {
                        $dm->getConnection()->connect();
                        if ($dm->getConnection()->isConnected()) return;
                    } catch (\MongoConnectionException $ignored) {
                    }
                }
            } catch (MongoTimeoutException $e) {
                self::stopMongo();
            }
        }
        throw new MongoTimeoutException("Exhausted retries to connect to mongodb");
    }

    public function stopMongo(): void {
        $absolutePath = self::getDBPath();
        if (self::$mongoProcess != null && !self::$mongoProcess->isRunning()) {
            self::$mongoProcess->stop();
        }
        $fs = new Filesystem();
        if ($fs->exists($absolutePath)) $fs->remove($absolutePath);
    }

    /**
     * @return string
     */
    private static function getDBPath(){
        return self::createClient()
                ->getContainer()
                ->getParameter('kernel.project_dir') . '/' . self::$dbPath;
    }


}