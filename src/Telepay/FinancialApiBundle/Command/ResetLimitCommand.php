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

class ResetLimitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:reset:limits')
            ->setDescription('Reset limits')
            ->addOption(
                'limit_name',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set Corresponding limits to 0.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:LimitCount');
        $limits=$repo->findAll();

        $repo_swift = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount');
        $limits_swift=$repo_swift->findAll();

        $type_limits=$input->getOption('limit_name');

        $contador=0;
        foreach($type_limits as $type){
            switch ($type) {
                case 'single':
                    foreach($limits as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    foreach($limits_swift as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    break;
                case 'day':
                    foreach($limits as $limit){
                        $limit->setDay(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    foreach($limits_swift as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    break;
                case 'week':
                    foreach($limits as $limit){
                        $limit->setWeek(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    foreach($limits_swift as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    break;
                case 'month':
                    foreach($limits as $limit){
                        $limit->setMonth(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    foreach($limits_swift as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    break;
                case 'year':
                    foreach($limits as $limit){
                        $limit->setYear(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    foreach($limits_swift as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    break;
                case 'total':
                    foreach($limits as $limit){
                        $limit->setTotal(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    foreach($limits_swift as $limit){
                        $limit->setSingle(0);
                        $em->persist($limit);
                        $em->flush();
                        $contador++;
                    }
                    break;
            }
        }

        $output->writeln($contador.' limits updated');

    }
}