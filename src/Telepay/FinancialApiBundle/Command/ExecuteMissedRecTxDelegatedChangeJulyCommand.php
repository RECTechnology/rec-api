<?php

declare(strict_types=1);

namespace Telepay\FinancialApiBundle\Command;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\ContainerAwareInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

/**
 * Class ExecuteMissedRecTxDelegatedChangeJulyCommand
 * @package Telepay\FinancialApiBundle\Command
 */
final class ExecuteMissedRecTxDelegatedChangeJulyCommand extends SynchronizedContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('rec:fix:jul2019')
            ->setDescription('Executes missed transactions blocked due to limit configurations in the 25th of July 2019.')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output) {

        $novactToCommerceTxIds = [
            "5d39f0cd4a0e535cad08a0dc",
            "5d39f0844a0e535c831992e6",
            "5d39f00c4a0e535c050f76f6",
            "5d39f0034a0e535c050f76f2",
            "5d39ef4e4a0e535b876fac32",
            "5d39ef2a4a0e535b5d473f2c",
            "5d39edc34a0e535a4c119a1c",
            "5d39ecff4a0e5359ce32d506",
            "5d39ec8c4a0e5359777e5658",
            "5d39ebdc4a0e5358f56c2eda",
            "5d39eb294a0e53587877e0da",
            "5d39eae74a0e53584d2fe2d8",
            "5d39ea624a0e5357f86fd4d2",
            "5d39ea264a0e5357cf62fef2",
            "5d39e9fb4a0e5357a50248ca",
            "5d39e8c74a0e5355e144cbd6",
            "5d39ed324a0e5359f8525ce2",
            "5d39e8944a0e53559331419a",
            "5d39e88c4a0e535593314196",
            "5d39e8834a0e535593314192",
            "5d39e85c4a0e53553d12b59c",
            "5d39e84f4a0e53553d12b596",
            "5d39e8214a0e5354eb612f4c",
            "5d39e80b4a0e5354eb612f42",
            "5d39e7924a0e535444230252",
            "5d39e7314a0e53539e4e38dc",
            "5d39e72d4a0e53539e4e38da",
            "5d39e6f44a0e53534a247c8c",
            "5d39e6ab4a0e5352f4225116",
            "5d39e67d4a0e5352a27d5c3c",
            "5d39e58b4a0e53514f40ed7c",
            "5d39e57a4a0e53514f40ed74",
            "5d39e54f4a0e5350fc1484ec",
            "5d39e54b4a0e5350fc1484ea",
            "5d39e4d74a0e53505734b6fc",
            "5d39e4d24a0e53505734b6fa",
            "5d39e4904a0e5350033f0736",
            "5d39e4614a0e534fb55cf35c",
            "5d39e1464a0e534d01328316",
            "5d39db2b4a0e534875256784",
        ];

        /** @var DocumentManager $dm */
        $dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $txRepo = $dm->getRepository(Transaction::class);
        foreach($novactToCommerceTxIds as $txId){
            print("Checking TX: $txId ... ");
            $tx = $txRepo->find($txId);
            if(!$tx) print("TX not found");
            else print_r($tx);
            print("\n----------------------------------\n");
            $params = [
                'amount' => $tx->getAmount(),
                'concept' => $tx->getDataOut()['concept'],
                'address' => $tx->getDataOut()['address'],
                'txid' => $tx->getDataOut()['txid'],
                'sender' => $tx->getGroup()->getId(),
                'internal_tx' => '1',
                'destination_id' => $tx->getDataOut()['destination_id']
            ];
            print("PARAMS TXin:\n");
            print_r($params);
            print("\n");

        }

        //$logger->info('(' . $group_id . ')(T) Incomig transaction... Create New');
        //$this->createTransaction($params, $version_number, 'in', $method_cname, $destination->getKycManager()->getId(), $destination, '127.0.0.1');
    }
}
