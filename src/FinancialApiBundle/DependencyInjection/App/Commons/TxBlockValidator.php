<?php


namespace App\FinancialApiBundle\DependencyInjection\App\Commons;


use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TransactionBlockLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TxBlockValidator
{

    /** @var ContainerInterface $container */
    private $container;
    private $tb;
    private $em;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function validateTxBlock(DelegatedChange $tb): array
    {
        /** @var EntityManagerInterface $em */
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->tb = $tb;
        $data_from_csv = $this->getTxListFromCSV();
        $res = $this->validateAllTx($data_from_csv);
        $res['data'] = $data_from_csv;
        return $res;
    }
    private function getTxListFromCSV(): array
    {
        $path = $this->tb->getUrlCsv();
        $tbd_list = [];
        $row = 0;
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = strval($row);
                if($row > 0) $tbd_list[$row] = $data;
                $row++;

            }
            fclose($handle);
        }
        return $tbd_list;
    }

    private function validateAllTx(array $tx_list): array
    {
        $senders_amount = [];
        $errors = [];
        $warnings = [];
        foreach ($tx_list as $tx_data) {
            $sender_id = $tx_data[3];
            if(array_key_exists($sender_id, $senders_amount)) $senders_amount[$sender_id] += floatval($tx_data[2]);
            else $senders_amount[$sender_id] = floatval($tx_data[2]);
            $val_res = $this->validateOneTx($tx_data);
            if (count($val_res['errors']) > 0) {
                $errors = $errors + $val_res['errors'];
            }
            if (count($val_res['warnings']) > 0){
                $warnings = $warnings + $val_res['warnings'];
            }
        }
        foreach ($senders_amount as $sender_id => $amount) {
            $sender = $this->em->getRepository(Group::class)->find($sender_id);
            if (isset($sender)) {
                if($sender->getWallets()[0]->getBalance() < $amount * 1e8){
                    $warn_text = 'Sender Account with id '.$sender_id.' has lower balance than amounts to send sum';
                    $warnings[] = $warn_text;
                    $this->persistLog(TransactionBlockLog::TYPE_WARN, $warn_text);
                }
            }

        }
        return ['result' => true, 'data' => [],'errors' => $errors, 'warnings' => $warnings];
    }

    private function validateOneTx(array $tx_data): array
    {
        $errors = [];
        $warnings = [];

        if ($tx_data[0] == $tx_data[1] || $tx_data[1] == $tx_data[3] || $tx_data[3] == $tx_data[0]) {
            $error_text = 'Account, Exchanger and Sender has to be different (row '.$tx_data[4].')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }

        $account = $this->em->getRepository(Group::class)->find($tx_data[0]);
        $exchanger = $this->em->getRepository(Group::class)->find($tx_data[1]);
        $sender = $this->em->getRepository(Group::class)->find($tx_data[3]);

        if (isset($account)){
            $account_warnings = $this->checkAccountState($account, $tx_data[4]);
            if(count($account_warnings) > 0)
                $warnings = $warnings + $account_warnings;
        }else{
            $error_text = 'Account with id '.$tx_data[0].' not found (row '.$tx_data[4].')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }
        if (isset($exchanger)) {
            $account_warnings = $this->checkAccountState($exchanger, $tx_data[4]);
            if(count($account_warnings) > 0)
                $warnings = $warnings + $account_warnings;
        }else{
            $error_text = 'Exchanger Account with id '.$tx_data[1].' not found (row '.$tx_data[4].')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }
        if (isset($sender)) {
            $account_warnings = $this->checkAccountState($sender, $tx_data[4]);
            if(count($account_warnings) > 0)
                $$warnings = $warnings + $account_warnings;
            if($sender->getWallets()[0]->getBalance() < floatval($tx_data[2]) * 1e8){
                $warn_text = 'Sender Account with id '.$tx_data[3].' has lower balance than amount (row '.$tx_data[4].')';
                $warnings[] = $warn_text;
                $this->persistLog(TransactionBlockLog::TYPE_WARN, $warn_text);
            }
        }else{
            $error_text = 'Sender Account with id '.$tx_data[3].' not found (row '.$tx_data[4].')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }


        return ['result' => true, 'data' => [],'errors' => $errors, 'warnings' => $warnings];
    }

    private function checkAccountState(Group $account, string $row): array
    {
        $warnings = [];
        if (!$account->getActive()){
            $warn_text = 'Account with id '.$account->getId().' is not active (row '.$row.')';
            $warnings[] = $warn_text;
            $this->persistLog(TransactionBlockLog::TYPE_WARN, $warn_text);
        }
        if (!$account->getKycManager()->isEnabled()){
            $warn_text = 'User of the account with id '.$account->getId().' is not enabled (row '.$row.')';
            $warnings[] = $warn_text;
            $this->persistLog(TransactionBlockLog::TYPE_WARN, $warn_text);
        }
        if (!$account->getKycManager()->isAccountNonLocked()) {
            $warn_text = 'User of the account with id '.$account->getId().' is locked (row '.$row.')';
            $warnings[] = $warn_text;
            $this->persistLog(TransactionBlockLog::TYPE_WARN, $warn_text);
        }
        return $warnings;
    }

    /**
     * @param string $type
     * @param string $error_text
     */
    private function persistLog(string $type, string $error_text): void
    {
        $matchText = explode('(row', $error_text)[0];
        $sameLog = $this->em->getRepository(TransactionBlockLog::class)->createQueryBuilder('l')
            ->where('l.block_txs = '.$this->tb->getId())
            ->andWhere("l.log LIKE '%$matchText%'")
            ->getQuery()
            ->getResult();

        if(count($sameLog) == 0){
            $log = new TransactionBlockLog();
            $log->setBlockTxs($this->tb);
            $log->setType($type);
            $log->setLog($error_text);
            $this->em->persist($log);
            $this->em->flush();
        }

    }

}