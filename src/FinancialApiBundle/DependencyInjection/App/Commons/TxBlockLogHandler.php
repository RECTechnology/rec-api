<?php


namespace App\FinancialApiBundle\DependencyInjection\App\Commons;


use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TransactionBlockLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TxBlockLogHandler
{

    /** @var ContainerInterface $container */
    private $container;
    private $em;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        /** @var EntityManagerInterface $em */
        $this->em = $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @param DelegatedChange $tb
     * @param string $type
     * @param string $error_text
     */
    public function persistLog(DelegatedChange $tb, string $type, string $error_text): void
    {
        $log = new TransactionBlockLog();
        $log->setBlockTxs($tb);
        $log->setType($type);
        $log->setLog($error_text);
        $this->em->persist($log);
        $this->em->flush();
    }

}