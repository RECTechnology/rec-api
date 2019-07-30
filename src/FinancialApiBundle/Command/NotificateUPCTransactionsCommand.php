<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\Transactions\Core\Notificator;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Document\Transaction;

/**
 * Class NotificateTransactionCommand
 * @package App\FinancialApiBundle\Command
 */
class NotificateUPCTransactionsCommand extends ContainerAwareCommand {

    /** @var Notificator $notificator */
    private $notificator;

    protected function configure()
    {
        $this
            ->setName('rec:notify:upc')
            ->setDescription('Notificate transactions to UPC since --since option')
            ->addOption(
                'since',
                null,
                InputOption::VALUE_OPTIONAL,
                'Since when you want to notify transactions to UPC? in relative format (see https://www.php.net/manual/en/datetime.formats.relative.php)',
                'midnight'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_OPTIONAL,
                'Force transactions to be notified?',
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
        $force = false;
        if($input->getOption('force') === 'yes'){
            $output->writeln("Forcing notificate");
        }
        elseif($input->getOption('force') !== 'no'){
            $output->writeln("ERROR: --force option must be 'yes' or 'no'");
            exit(-1);
        }


        $sinceOption = $input->getOption("since");
        try {
            $since = new \DateTime($sinceOption);
        } catch (\Exception $e) {
            $output->writeln("ERROR: invalid --since parameter, see https://www.php.net/manual/en/datetime.formats.relative.php");
            exit(-2);
        }
        $since->setTimezone(new \DateTimeZone("Europe/Madrid"));
        if(new \DateTime('now') < $since) {
            $output->writeln("ERROR: --since parameter must be in the past");
            exit(-2);
        }
        $output->writeln("Searching transaction since " . $since->format('Y-m-d\TH-i-sO') );

        /** @var DocumentManager $dm */
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        /** @var TransactionRepository $txRepo */
        $txRepo = $dm->getRepository(Transaction::class);

        /** @var EntityManagerInterface $dm */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var ObjectRepository $txRepo */
        $accRepo = $em->getRepository(Group::class);

        $bmincomers = array_map(
            function(Group $acc){ return $acc->getId();},
            $accRepo->findBy(['type' => 'PRIVATE', 'subtype' => 'BMINCOME'])
        );

        $txs = $txRepo->createQueryBuilder()
            ->field('updated')->gte($since)
            ->field('status')->equals(Transaction::$STATUS_SUCCESS)
            ->field('group')->in($bmincomers)
            ->getQuery()
            ->execute();

        $numTx = count($txs);
        $output->writeln("Found {$numTx} Transactions so far");

        if($numTx > 0) {
            /** @var Transaction $tx */
            foreach($txs as $tx){
                $output->writeln('Transaction => ' . $tx->getId());
                $tx = $this->notificator->notificate($tx, $force);
                if($tx->getNotified()){
                    $output->writeln('Transaction notified successfully');
                }else{
                    $output->writeln('Transaction notification FAILED');
                }
            }
        }
    }

}