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
        $csv_content = str_replace('"','', file_get_contents($path));
        $rows = explode(PHP_EOL, $csv_content);
        if (substr_count($rows[0], ',') > substr_count($rows[0], ';')){
            $separator = ',';
        }else{
            $separator = ';';
        }

        $tbd_list = [];
        $row = 0;
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
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
            $tx_sender_id = $tx_data[0];
            $tx_amount = floatval($tx_data[3]);
            if(array_key_exists($tx_sender_id, $senders_amount)) $senders_amount[$tx_sender_id] += floatval($tx_amount);
            else $senders_amount[$tx_sender_id] = floatval($tx_amount);
            $val_res = $this->validateOneTx($tx_data);
            if (count($val_res['errors']) > 0) {
                $errors = $errors + $val_res['errors'];
            }
            if (count($val_res['warnings']) > 0){
                $warnings = $warnings + $val_res['warnings'];
            }
        }
        foreach ($senders_amount as $tx_sender_id => $amount) {
            $sender = $this->em->getRepository(Group::class)->find($tx_sender_id);
            if (isset($sender)) {
                $sender_balance = $sender->getWallets()[0]->getBalance();
                if($sender_balance < $amount * 1e8){
                    //$warn_text = 'Sender Account with id '.$sender_id.' has lower balance than amounts to send sum';
                    $warn_text = 'The sender '.$tx_sender_id.' must send '.$amount.'R but only has '.$sender_balance.'R.
                     This will cause an ERROR in the sending';
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
        $tx_sender_id = $tx_data[0];
        $tx_excahnger_id = $tx_data[1];
        $tx_account_id = $tx_data[2];
        $tx_amount = $tx_data[3];
        $row = $tx_data[4];

        if ($tx_sender_id === $tx_excahnger_id) {
            $error_text = 'Sender '.$tx_sender_id.' cannot send money to intermediary '.$tx_excahnger_id.'. 
            They are the same account (row '.$row.')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }


        if ($tx_excahnger_id === $tx_account_id) {
            $error_text = 'Intermediary '.$tx_excahnger_id.' cannot send money to beneficiary '.$tx_account_id.'. 
            They are the same account (row '.$row.')';

            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }

        $sender = $this->em->getRepository(Group::class)->find($tx_sender_id);
        $exchanger = $this->em->getRepository(Group::class)->find($tx_excahnger_id);
        $account = $this->em->getRepository(Group::class)->find($tx_account_id);

        if (isset($account)){
            $account_warnings = $this->checkAccountState($account, $row);
            if(count($account_warnings) > 0)
                $warnings = $warnings + $account_warnings;
        }else{
            $error_text = 'Account with id '.$tx_amount.' not found (row '.$row.')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }
        if (isset($exchanger)) {
            $account_warnings = $this->checkAccountState($exchanger, $row);
            if(count($account_warnings) > 0)
                $warnings = $warnings + $account_warnings;
        }else{
            $error_text = 'Exchanger Account with id '.$tx_excahnger_id.' not found (row '.$row.')';
            $errors[] = $error_text;
            $this->persistLog(TransactionBlockLog::TYPE_ERROR, $error_text);
        }
        if (isset($sender)) {
            $account_warnings = $this->checkAccountState($sender, $row);
            if(count($account_warnings) > 0)
                $$warnings = $warnings + $account_warnings;

            if($sender->getWallets()[0]->getBalance() < floatval($tx_amount) * 1e8){
                $warn_text = 'Sender Account with id '.$tx_sender_id.' has lower balance than amount (row '.$row.')';
                $warnings[] = $warn_text;
                $this->persistLog(TransactionBlockLog::TYPE_WARN, $warn_text);
            }
        }else{
            $error_text = 'Sender Account with id '.$tx_sender_id.' not found (row '.$row.')';
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
            $log_handler = $this->container->get('net.app.commons.tx_block_log_handler');
            $log_handler->persistLog($this->tb, $type, $error_text);
        }

    }

}