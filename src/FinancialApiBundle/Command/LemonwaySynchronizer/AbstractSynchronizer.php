<?php


namespace App\FinancialApiBundle\Command\LemonwaySynchronizer;


use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSynchronizer implements Synchronizer {

    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var LemonWayInterface
     */
    protected $lw;
    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(EntityManagerInterface $em, LemonWayInterface $lw, OutputInterface $output)
    {
        $this->em = $em;
        $this->lw = $lw;
        $this->output = $output;
    }

    function getWalletsIndexedByLemonId(){

        $repo = $this->em->getRepository(Group::class);
        $accounts = $repo->findBy(['type' => 'COMPANY']);

        $index = [];
        /** @var Group $account */
        foreach ($accounts as $account){
            $this->output->writeln("[INFO] Indexing account {$account->getId()}");
            if($account->getLwBalance() != null){
                $wid = strtoupper($account->getCif());
                if(!$wid || strlen($wid) == 0)
                    $this->output->writeln("[WARN] CIF for account {$account->getId()} is null or empty");
                else {
                    if (!key_exists($wid, $index)) $index[$wid] = [];
                    $index[$wid] []= $account;
                }
            }
            else {
                $this->output->writeln("[WARN] Null lw_balance found in {$account->getId()}, ignoring account");
            }
        }
        return $index;
    }


}