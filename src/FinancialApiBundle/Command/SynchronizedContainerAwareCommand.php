<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

abstract class SynchronizedContainerAwareCommand extends ContainerAwareCommand {

    abstract protected function executeSynchronized(InputInterface $input, OutputInterface $output);

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output){
        $lock = new LockHandler($this->getName() . '.lock');
        if(!$lock->lock()) {
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
    }

}