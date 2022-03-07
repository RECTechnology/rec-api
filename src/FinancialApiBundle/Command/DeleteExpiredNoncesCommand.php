<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\PaymentOrderUsedNonce;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteExpiredNoncesCommand
 * @package App\FinancialApiBundle\Command
 */
class DeleteExpiredNoncesCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:pos:delete:expired_nonces')
            ->setDescription('Delete all expired nonces')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Removing expired nonces ...');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(PaymentOrderUsedNonce::class);
        $nowTimestamp = round(microtime(true) * 1000, 0);
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->lt('nonce', $nowTimestamp - 300000));

        $nonces = $repo->matching($criteria);


        $output->writeln(count($nonces).' nonces found to remove');

        /** @var PaymentOrderUsedNonce $nonceObject */
        foreach($nonces as $nonceObject){
            $em->remove($nonceObject);
            $em->flush();
        }

        $output->writeln(count($nonces).' nonces removed successfully');

    }

}