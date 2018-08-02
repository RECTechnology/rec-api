<?php

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

class LemonRegisterAndKYCCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:lemon:register')
            ->setDescription('Lemon register')
            ->addOption(
                'commerce_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Commerce id to register in lemonWay',
                null
            )
        ;
    }

    public $commerce_id;

    protected function execute(InputInterface $input, OutputInterface $output){

        if($input->getOption('commerce_id')){
            $this->commerce_id = $input->getOption('commerce_id');
            $em = $this->getContainer()->get('doctrine')->getManager();
            $providerName = 'net.telepay.in.lemonway.v1';
            $moneyProvider = $this->getContainer()->get($providerName);

            $company=$em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'id' => $this->commerce_id
            ));
            if($company){
                $user=$em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                    'id' => $company->getKycManager()
                ));

                $KYC=$em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user->getId()
                ));

                $user->setEmail('ivan14@robotunion.org');
                $new_account = $moneyProvider->RegisterWallet('company-' . $company->getId(), $user->getEmail(), $KYC->getName(), $KYC->getLastName(), 'M');
                $text='register=>' . json_encode($new_account, JSON_PRETTY_PRINT);
                $output->writeln($text);

                if(!property_exists($new_account, 'WALLET') && isset($new_account['REGISTERWALLET']) && isset($new_account['REGISTERWALLET']['STATUS']) && $new_account['REGISTERWALLET']['STATUS'] == '-1'){
                    $output->writeln('Register command error: ' . $new_account['REGISTERWALLET']['MESSAGE']);
                    exit(0);
                }

                $lemon_id = $new_account->WALLET->LWID;
                $company->setLemonId($lemon_id);
                $em->persist($company);
                $em->flush();

                /*
                $filename = "";
                $buffer = base64_encode(file_get_contents('images/' . $filename, true));
                $up_file = $moneyProvider->UploadFile($company->getLemonId(), $filename, $type, $buffer);

                echo "\n<pre>\n".json_encode($response, JSON_PRETTY_PRINT)."\n</pre>\n";
                */
                $output->writeln($text);
            }
            else{
                $output->writeln('Commerce not found');
            }
        }
        else{
            $output->writeln('Commerce id not defined');
        }
        $output->writeln('End');
    }
}