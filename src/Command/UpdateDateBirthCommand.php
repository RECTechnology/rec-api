<?php

namespace App\Command;

use App\Entity\KYC;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class UpdateDateBirthCommand extends SynchronizedContainerAwareCommand{

    protected function configure()
    {
        $this->setName('rec:update:date_birth')
            ->setDescription("Get all dateBirth and change to datetime");
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        //get all KYC
        $kycs = $em->getRepository(KYC::class)->findAll();
        $helper = $this->getHelper('question');
        $output->writeln(count($kycs).' kycs to check');
        /** @var KYC $kyc */
        foreach ($kycs as $kyc){
            $dateBirthWithoutTime = str_replace("T00:00:00.000", "", $kyc->getDateBirth());
            $dateBirth = str_replace("/", "-", $dateBirthWithoutTime);

            if($dateBirth){
                if($this->isValidDateBirth($dateBirth)){
                    $kyc->setDateBirth($dateBirth);
                }else{
                    $output->writeln($kyc->getDateBirth());
                    $output->writeln('NOT VALID');
                    $question = new Question('Please enter a valid date(press enter to save as null)(example 1989-08-23):', '');

                    $newDateBirth = $helper->ask($input, $output, $question);
                    if($newDateBirth && $this->isValidDateBirth($newDateBirth)){
                        $kyc->setDateBirth($newDateBirth);
                    }else{
                        if($newDateBirth){
                            $output->writeln('Invalid format. Execute the command again to fix this item, Now continue fixing other items');
                        }else{
                            $kyc->setDateBirth($newDateBirth);
                        }

                    }
                }

            }else{
                $kyc->setDateBirth(null);
            }
            $em->flush();
        }
    }

    private function isValidDateBirth($date){
        $format = 'Y-m-d';
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt && $dt->format($format) === $date;
    }
}