<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class CheckMonthCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:check:month')
            ->setDescription('Check month phone')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        $search = '603270565';
        $search = '606352522';
        $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00'))-31*24*3600);
        $finish_time = new \MongoDate();
        $result = $qb
            ->field('created')->gte($start_time)
            ->field('created')->lte($finish_time)
            ->field('method_out')->equals('halcash_es')
            ->field('status')->equals('created','success')
            ->field('id')->equals('56f0f60ac2e96538328b4567')
//            ->where("function(){
//                    if (typeof this.pay_out_info.phone !== 'undefined') {
//                        if(String(this.pay_out_info.phone).indexOf('$search') > -1){
//                            return true;
//                        }
//                    }
//                    return false;
//                }")

            ->getQuery()
            ->execute();

        $pending=0;

        foreach($result->toArray() as $d){
            $pending = $pending + $d->getAmount();
        }

        $output->writeln($pending.' total');
    }
}