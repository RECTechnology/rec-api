<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\Transactions\Core\Notificator;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
                'Since when you want to notify transactions to UPC? (default: midnight) in relative format (see https://www.php.net/manual/en/datetime.formats.relative.php)',
                'midnight'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_OPTIONAL,
                'Force transactions to be notified? (default: no)',
                'no'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Simulate only? (default: yes)',
                'yes'
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
            $force = true;
            $output->writeln("Forcing notificate all transactions even it is already notified");
        }
        elseif($input->getOption('force') !== 'no'){
            $output->writeln("ERROR: --force option must be 'yes' or 'no'");
            exit(-1);
        }
        else {
            $output->writeln("Notifying only non-notified txs");
        }

        $dryRun = true;
        if($input->getOption('dry-run') === 'no'){
            $dryRun = false;
            $output->writeln("Simulate is turned off, real notify");
        }
        elseif($input->getOption('dry-run') !== 'yes'){
            $output->writeln("ERROR: --dry-run option must be 'yes' or 'no'");
            exit(-1);
        }
        else {
            $output->writeln("Simulating only, this will not notify any transaction.");
        }


        $sinceOption = $input->getOption("since");
        try {
            $since = new \DateTime($sinceOption);
            $since->setTimezone(new \DateTimeZone("Europe/Madrid"));
        } catch (\Exception $e) {
            $output->writeln("ERROR: invalid --since parameter, see https://www.php.net/manual/en/datetime.formats.relative.php");
            exit(-2);
        }
        if(new \DateTime('now') < $since) {
            $output->writeln("ERROR: --since parameter must be in the past");
            exit(-2);
        }
        $output->writeln("Searching transaction since " . $since->format('c') );

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

        $countBmincomers = count($bmincomers);
        $output->writeln("Found {$countBmincomers} Total BMIncomers ");

        $isoSince = $since->format('c');

        $q = $txRepo->createQueryBuilder()
            ->field('status')->equals(Transaction::$STATUS_SUCCESS)
            ->field('group')->in($bmincomers)
            //This is done like this because half of database is in string and the other half is in ISODate
            ->where("function(){
                if(this.updated instanceof Date)
                    return (this.updated > ISODate('$isoSince'));
                return (this.updated > '$isoSince');
            }")
            ->getQuery();

        $txs = $q->execute();

        $numTx = count($txs);
        $output->writeln("Found {$numTx} Transactions so far");

        if($numTx > 0) {
            /** @var Transaction $tx */
            foreach($txs as $tx){
                $output->writeln('Transaction => ' . $tx->getId());
                if($dryRun){
                    $output->writeln('Started Notify [Simulated]');
                    $tx->setNotified(true);
                }
                else {
                    $output->writeln('Started Notify [Real]');
                    $tx = $this->notificator->notificate($tx, $force);
                }
                if($tx->getNotified()){
                    $output->writeln('Transaction notified successfully');
                }else{
                    $output->writeln('Transaction notification FAILED');
                }
            }
        }
    }

}