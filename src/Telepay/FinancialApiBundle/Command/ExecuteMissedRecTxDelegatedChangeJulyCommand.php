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
            "5d39e9834a0e534ef11de058",
            "5d39e9574a0e534ef11de04e",
            "5d39e91c4a0e534ef11de040",
            "5d39e9144a0e534ef11de03e",
            "5d39e8c14a0e534ef11de02b",
            "5d39e8bd4a0e534ef11de02a",
            "5d39e81f4a0e534ef11de006",
            "5d39e7c44a0e534ef11ddff1",
            "5d39e7904a0e534ef11ddfe5",
            "5d39e7474a0e534ef11ddfd4",
            "5d39e6f94a0e534ef11ddfc2",
            "5d39e6db4a0e534ef11ddfbb",
            "5d39e69c4a0e534ef11ddfac",
            "5d39e6834a0e534ef11ddfa6",
            "5d39e67b4a0e534ef11ddfa4",
            "5d39e5f34a0e534ef11ddf84",
            "5d39e5e74a0e534ef11ddf81",
            "5d39e5e04a0e534ef11ddf80",
            "5d39e5d84a0e534ef11ddf7e",
            "5d39e5cf4a0e534ef11ddf7c",
            "5d39e5cb4a0e534ef11ddf7b",
            "5d39e5bf4a0e534ef11ddf78",
            "5d39e5b14a0e534ef11ddf75",
            "5d39e59d4a0e534ef11ddf70",
            "5d39e5684a0e534ef11ddf64",
            "5d39e54b4a0e534ef11ddf5d",
            "5d39e5474a0e534ef11ddf5c",
            "5d39e5324a0e534ef11ddf57",
            "5d39e5064a0e534ef11ddf4d",
            "5d39e4fa4a0e534ef11ddf4a",
            "5d39e48f4a0e534ef11ddf31",
            "5d39e47d4a0e534ef11ddf2d",
            "5d39e4754a0e534ef11ddf2b",
            "5d39e4714a0e534ef11ddf2a",
            "5d39e4374a0e534ef11ddf1d",
            "5d39e4324a0e534ef11ddf1c",
            "5d39e4104a0e534ef11ddf14",
            "5d39e3fe4a0e534ef11ddf10",
            "5d39e10a4a0e534ca84251b5",
            "5d39db0b4a0e5348453a3da3",
        ];

        /** @var DocumentManager $dm */
        $dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $txRepo = $dm->getRepository(Transaction::class);
        foreach($novactToCommerceTxIds as $txId){
            print("Checking TX: $txId ... ");
            $tx = $txRepo->find($txId);
            if(!$tx) print("TX not found");
            else print_r($tx);
            print("\n");
        }

        /*
        $params = array(
            'amount' => $amount,
            'concept' => $concept,
            'address' => $address,
            'txid' => $txid,
            'sender' => $group->getId()
        );
        if(isset($data['internal_tx']) && $data['internal_tx']=='1') {
            $params['internal_tx']='1';
            $params['destionation_id']=$data['destionation_id'];
        }
        $logger->info('(' . $group_id . ')(T) Incomig transaction... Create New');
        $this->createTransaction($params, $version_number, 'in', $method_cname, $destination->getKycManager()->getId(), $destination, '127.0.0.1');
*/
    }
}
