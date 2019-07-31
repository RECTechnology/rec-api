<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\Transactions\Core\Notificator;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Document\Transaction;

/**
 * Class NotificateTransactionCommand
 * @package App\FinancialApiBundle\Command
 */
class NotificateTransactionCommand extends ContainerAwareCommand {

    /** @var Notificator $notificator */
    private $notificator;

    protected function configure()
    {
        $this
            ->setName('rec:notificate:transaction')
            ->setDescription('Notificate a transaction passind the id')
            ->addOption(
                'tx',
                null,
                InputOption::VALUE_REQUIRED,
                'What transaction do you want to notificate? (provide transaction id)'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_OPTIONAL,
                'Force transaction to be notified?',
                'no'
            )
        ;
    }

    /**
     * @required
     * @param Notificator $notificator
     */
    public function setNotificator(Notificator $notificator){
        $this->notificator = $notificator;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Searching transaction');
        $id = $input->getOption('tx');
        $force = false;
        if($input->getOption('force') === 'yes'){
            $output->writeln("Forcing notificate");
        }
        elseif($input->getOption('force') !== 'no'){
            $output->writeln("ERROR: --force option must be 'yes' or 'no'");
            exit(-1);
        }

        /** @var DocumentManager $dm */
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        /** @var TransactionRepository $txRepo */
        $txRepo = $dm->getRepository(Transaction::class);

        /** @var Transaction $tx */
        $tx = $txRepo->find($id);

        if($tx) {
            $output->writeln('Transaction => ' . $tx->getId());
            $url = ($tx->getDataIn()? $tx->getDataIn(): $tx->getDataOut())['url_notification'];
            $output->writeln('Url notification => ' . $url);

            $output->writeln('Sending notification');

            $tx = $this->notificator->notificate($tx, $force);

            if($tx->getNotified()){
                $output->writeln('NOTIFICATED TRANSACTION');
            }else{
                $output->writeln('Notification FAILED');
            }

        }else{
            $output->writeln("Transaction {$id} not found");
        }

    }

}