<?php

namespace App\Command;


use App\Entity\Group;
use App\Entity\KYC;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\Entity\UserWallet;
use App\Financial\Currency;

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
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $providerName = 'net.app.in.lemonway.v1';
            $moneyProvider = $this->getContainer()->get($providerName);

            $company=$em->getRepository(Group::class)->findOneBy(array(
                'id' => $this->commerce_id
            ));
            if($company){
                $user=$em->getRepository(User::class)->findOneBy(array(
                    'id' => $company->getKycManager()
                ));

                $KYC=$em->getRepository(KYC::class)->findOneBy(array(
                    'user' => $user->getId()
                ));

                //ls
                //for

                if(true) {
                    $is_individual = true;
                }

                //Individual
                if($is_individual) {
                    $email = $user->getEmail();
                    if($email == ''){
                        $output->writeln('User email is empty.');
                        exit(0);
                    }
                    $name = $KYC->getName();
                    if($name == ''){
                        $output->writeln('KYC manager name is empty.');
                        exit(0);
                    }
                    $lastName = $KYC->getLastName();
                    if($lastName == ''){
                        $output->writeln('KYC manager lastname is empty.');
                        exit(0);
                    }
                    $date_birth = $KYC->getDateBirth();
                    if($date_birth == ''){
                        $output->writeln('KYC manager date_birth is empty.');
                        exit(0);
                    }

                    $nationality = $KYC->getNationality();
                    $gender = $KYC->getGender();

                    $address = $company->getStreetType() . " " . $company->getStreet() . " " . $company->getAddressNumber();
                    if(str_replace(' ', '', $address) == ''){
                        $output->writeln('KYC manager address is empty.');
                        exit(0);
                    }
                    $zip = $company->getZip();
                    if($zip == ''){
                        $output->writeln('KYC manager zip is empty.');
                        exit(0);
                    }
                    $city = $company->getCity();
                    if($city == ''){
                        $output->writeln('KYC manager city is empty.');
                        exit(0);
                    }
                    $country = $company->getCountry();
                    if($country == ''){
                        $output->writeln('KYC manager country is empty.');
                        exit(0);
                    }
                    $new_account = $moneyProvider->RegisterWalletIndividual($user->getDNI(), $email, $name, $lastName, $date_birth, $nationality, $gender, $address, $zip, $city, $country);
                    $text = 'register=>' . json_encode($new_account, JSON_PRETTY_PRINT);
                    $output->writeln($text);
                }
                //Profesional
                else{
                    $email = $user->getEmail();
                    if($email == ''){
                        $output->writeln('User email is empty.');
                        exit(0);
                    }
                    $company_name = $company->getName();
                    if($company_name == ''){
                        $output->writeln('KYC company name is empty.');
                        exit(0);
                    }
                    $company_web = 'rec.barcelona';
                    $company_description = $company->getDescription();
                    if($company_description == ''){
                        $output->writeln('KYC company description is empty.');
                        exit(0);
                    }
                    $name = $KYC->getName();
                    if($name == ''){
                        $output->writeln('KYC manager name is empty.');
                        exit(0);
                    }
                    $lastName = $KYC->getLastName();
                    if($lastName == ''){
                        $output->writeln('KYC manager lastname is empty.');
                        exit(0);
                    }
                    $date_birth = $KYC->getDateBirth();
                    if($date_birth == ''){
                        $output->writeln('KYC manager date_birth is empty.');
                        exit(0);
                    }

                    $nationality = 'ESP';
                    $gender = 'M';

                    $address = $company->getStreetType() . " " . $company->getStreet() . " " . $company->getAddressNumber();
                    if(str_replace(' ', '', $address) == ''){
                        $output->writeln('KYC manager address is empty.');
                        exit(0);
                    }
                    $zip = $company->getZip();
                    if($zip == ''){
                        $output->writeln('KYC manager zip is empty.');
                        exit(0);
                    }
                    $city = $company->getCity();
                    if($city == ''){
                        $output->writeln('KYC manager city is empty.');
                        exit(0);
                    }
                    $country = $company->getCountry();
                    if($country == ''){
                        $output->writeln('KYC manager country is empty.');
                        exit(0);
                    }
                    $new_account = $moneyProvider->RegisterWalletCompany($company->getCIF(), $email, $company_name, $company_web, $company_description, $name, $lastName, $date_birth, $nationality, $gender, $address, $zip, $city, $country);
                    $text = 'register=>' . json_encode($new_account, JSON_PRETTY_PRINT);
                    $output->writeln($text);
                }

                if(!is_object($new_account) && isset($new_account['REGISTERWALLET']) && isset($new_account['REGISTERWALLET']['STATUS']) && $new_account['REGISTERWALLET']['STATUS'] == '-1'){
                    $output->writeln('Register command error: ' . $new_account['REGISTERWALLET']['MESSAGE']);
                    exit(0);
                }

                $lemon_id = $new_account->WALLET->LWID;
                $company->setLemonId($lemon_id);
                $em->persist($company);
                $em->flush();

                $user_dni = $user->getDNI();

                //DNI front
                $output->writeln('DNI front');
                $filename = "id_front.jpeg";
                $type = 0;
                $dni_file = $KYC->getDocumentFront();
                $datos = explode("/", $dni_file);
                $file = $datos[3];
                $buffer = base64_encode(file_get_contents('/home/bmoneda/files/' . $file, true));
                $up_file = $moneyProvider->UploadFile($user_dni, $filename, $type, $buffer);
                echo "\n<pre>\n".json_encode($up_file, JSON_PRETTY_PRINT)."\n</pre>\n";

                //DNI rear
                $output->writeln('DNI rear');
                $filename = "id_back.jpeg";
                $type = 1;
                $dni_file = $KYC->getDocumentRear();
                $datos = explode("/", $dni_file);
                $file = $datos[3];
                $buffer = base64_encode(file_get_contents('/home/bmoneda/files/' . $file, true));
                $up_file = $moneyProvider->UploadFile($user_dni, $filename, $type, $buffer);
                echo "\n<pre>\n".json_encode($up_file, JSON_PRETTY_PRINT)."\n</pre>\n";

                //IBAN
                $output->writeln('IBAN');
                $filename = "iban.jpg";
                $type = 2;
                $buffer = base64_encode(file_get_contents('/home/bmoneda/files/REC/' . $user_dni . "/" . $user_dni . "-IBAN.pdf", true));
                $up_file = $moneyProvider->UploadFile($user_dni, $filename, $type, $buffer);
                echo "\n<pre>\n".json_encode($up_file, JSON_PRETTY_PRINT)."\n</pre>\n";

                /*
                //Passport
                $output->writeln('Passport');
                $filename = "passport.jpg";
                $type = 3;
                $user_dni = $user->getDNI();
                $buffer = base64_encode(file_get_contents('/home/bmoneda/files/REC/' . $user_dni . "/" . $user_dni . "-.pdf", true));
                $up_file = $moneyProvider->UploadFile($user_dni, $filename, $type, $buffer);
                echo "\n<pre>\n".json_encode($up_file, JSON_PRETTY_PRINT)."\n</pre>\n";
                */

                //Individual
                if($is_individual) {
                    //Alta autonomos
                    $output->writeln('Alta autonomos');
                    $filename = "autonomos.jpg";
                    $type = 6;
                    $user_dni = $user->getDNI();
                    $buffer = base64_encode(file_get_contents('/home/bmoneda/files/REC/' . $user_dni . "/" . $user_dni . "-autonomos.pdf", true));
                    $up_file = $moneyProvider->UploadFile($user_dni, $filename, $type, $buffer);
                    echo "\n<pre>\n" . json_encode($up_file, JSON_PRETTY_PRINT) . "\n</pre>\n";

                    //Modelo 036 o 037
                    $output->writeln('Modelo 036 o 037');
                    $filename = "modelo.jpg";
                    $type = 7;
                    $user_dni = $user->getDNI();
                    $buffer = base64_encode(file_get_contents('/home/bmoneda/files/REC/' . $user_dni . "/" . $user_dni . "-037.pdf", true));
                    $up_file = $moneyProvider->UploadFile($user_dni, $filename, $type, $buffer);
                    echo "\n<pre>\n" . json_encode($up_file, JSON_PRETTY_PRINT) . "\n</pre>\n";
                }
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