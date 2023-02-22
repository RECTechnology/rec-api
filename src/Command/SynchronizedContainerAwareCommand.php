<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

abstract class SynchronizedContainerAwareCommand extends Command {

    abstract protected function executeSynchronized(InputInterface $input, OutputInterface $output);

    protected $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output){
        $factory = new LockFactory(new SemaphoreStore());
        $lock = $factory->createLock($this->getName() . '.lock');
        if(!$lock->acquire()) {
            $output->writeln("Command '" . $this->getName() . "' execution locked by another command");
            exit(-1);
        }
        try{
            $this->executeSynchronized($input, $output);
        }catch (\Exception $e){

            $output->writeln("Command exited with exception: " . $e->getMessage());
            $lock->release();
            throw $e;
        }
        $lock->release();
        return 0;
    }

}