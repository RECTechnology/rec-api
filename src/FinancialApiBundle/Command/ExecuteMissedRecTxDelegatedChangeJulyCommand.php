<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Command;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\ContainerAwareInterface;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;

/**
 * Class ExecuteMissedRecTxDelegatedChangeJulyCommand
 * @package App\FinancialApiBundle\Command
 */
final class ExecuteMissedRecTxDelegatedChangeJulyCommand extends SynchronizedContainerAwareCommand {




    protected function configure() {
        $this
            ->setName('rec:fix:jul2019')
            ->setDescription('Executes missed transactions blocked due to limit configurations in the 25th of July 2019.')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output) {


        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var DocumentManager $dm */
        $dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $txRepo = $dm->getRepository(Transaction::class);
        $accRepo = $em->getRepository(Group::class);
        $usRepo = $em->getRepository(User::class);

        $qb = $txRepo->createQueryBuilder();
        $qb2 = $txRepo->createQueryBuilder();

        $now = new \DateTime();
        $before4days = $now->sub(new \DateInterval('P5D'));
        $txs = $qb
            ->field('method')->equals('rec')
            ->field('type')->equals('in')
            ->field('internal')->equals(true)
            ->field('pay_in_info.destionation_id')->exists(true)
            ->field('created')->gt($before4days)
            ->getQuery()
            ->execute();

        $output->writeln("COUNT: " . count($txs));
        $bad = [];
        /** @var Transaction $tx */
        foreach($txs as $tx){;
            $user_id = $tx->getPayInInfo()['destionation_id'];
            $txsFound = $qb2
                ->field('method')->equals('rec')
                ->field('type')->equals('in')
                ->field('group')->equals($user_id)
                ->field('created')->gt($before4days)
                ->getQuery()
                ->execute();
            if(count($txsFound) == 0) {
                $bad [] = $tx;
                $output->writeln("BAD TX: " . $tx->getId());
            }
            # $output->writeln("tx found for user $user_id: " . count($txsFound));
        }
        $output->writeln("bad txs: " . count($bad));



        /** @var Transaction $tx */
        foreach ($bad as $tx){
            $bmincomerId = $tx->getPayInInfo()['destionation_id'];
            $bmincomer = $accRepo->find($bmincomerId);

            $intermediaryId = $tx->getGroup();
            $intermediary = $accRepo->find($intermediaryId);
            $intermediaryManagerId = $tx->getUser();
            $intermediaryManager = $usRepo->find($intermediaryManagerId);

            $request = [];
            $request['concept'] = 'Internal exchange';
            $request['amount'] = $tx->getAmount();
            $request['address'] = $bmincomer->getRecAddress();
            $request['pin'] = $intermediaryManager->getPIN();
            $request['internal_out'] = '1';

            $output->writeln("NEW TX:");
            $output->writeln("params: " . print_r($request));
            $output->writeln("receiver account_id: " . $bmincomerId);
            $output->writeln("receiver type: " . $bmincomer->getType());
            $output->writeln("commerce user_id: " . $intermediaryManagerId);
            $output->writeln("commerce account_id: " . $intermediaryId);

            /*
            $output->writeln('get app');
            $transactionManager = $this->getContainer()->get('app.incoming_controller');
            $output->writeln('createTransaction');
            $response = $transactionManager->createTransaction($request, 1, 'out', 'rec', $intermediaryManagerId, $intermediary, '127.0.0.1');
            $output->writeln('post createTransaction');
            $output->writeln($response);
        */
        }
    }


}
