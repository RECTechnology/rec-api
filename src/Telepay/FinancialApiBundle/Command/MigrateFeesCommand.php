<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\ResellerDealer;

class MigrateFeesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:fees')
            ->setDescription('Migrate fees to new reselling system.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();
        $output->writeln(count($companies).' companies');

        //TODO recorrer todas las companies
        foreach ($companies as $company){
            $output->writeln('COMPANY '.$company->getname());
            $this->createResellerDealer($company, $company, $output);
        }

    }

    private function createResellerDealer($company, $company_orig, OutputInterface $output){

        $em = $this->getContainer()->get('doctrine')->getManager();

        $creator = $company->getGroupCreator();
        $output->writeln('CREATOR '.$creator->getName());

        //get fees for this creator if is diferent to root
        if($creator->getId() != $this->getContainer()->getParameter('id_group_root')){
            $fees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findBy(array(
                'group' =>  $creator
            ));

            //generate reseller dealer line
            foreach ($fees as $fee){
                $output->writeln('METHOD '.$fee->getServiceName());
                $resellerDealer = new ResellerDealer();
                $resellerDealer->setMethod($fee->getServiceName());
                $resellerDealer->setCompanyOrigin($company_orig);
                $resellerDealer->setCompanyReseller($creator);

                //para saber el porcentaje necesito saber el total
                $output->writeln('GETTING ORIGINAL VARIABLE');
                $origFee = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findOneBy(array(
                    'group' =>  $company_orig,
                    'service_name'  =>  $fee->getServiceName()
                ));

                if(!$origFee){
                    $origVariable = 0;
                }else{
                    $origVariable = $origFee->getVariable();
                }

                $output->writeln('GETTING ACTUAL VARIABLE');
                $actualVariable = $fee->getVariable();
                //esto es lo que se queda esta company
                //la fee del anterior menos la suya
                if($company->getid() != $company_orig->getId()){
                    $output->writeln('COMPANY AND ORIG COMPANY ARE DIFERENTS');
                    $previousFee = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findOneBy(array(
                        'group' =>  $company,
                        'service_name'  =>  $fee->getServiceName()
                    ));
                    if(!$previousFee){
                        $anteriorFee = 0;
                    }else{

                        $anteriorFee = $previousFee->getVariable();
                    }
                }else{
                    $output->writeln('COMPANY AND ORIG COMPANY ARE THE SAME');
                    $anteriorFee = $origVariable;
                }

                $absoluteVariable = $anteriorFee - $actualVariable;


                $output->writeln('ORIGINAL '.$origVariable.' - ANTERIOR '.$anteriorFee.' -  ACTUAL '.$actualVariable.' - ABSOLUTE '.$absoluteVariable );
                if($origVariable == 0){
                    $newFee = 0;
                }else{
                    $newFee = ($absoluteVariable * 100) / $origVariable;
                }
                $resellerDealer->setFee($newFee);
                $output->writeln('NEW FEE '.$newFee);

            }
            $this->createResellerDealer($creator, $company_orig, $output);
        }
    }


}