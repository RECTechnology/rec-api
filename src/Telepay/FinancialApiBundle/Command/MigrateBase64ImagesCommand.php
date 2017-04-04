<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MigrateBase64ImagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:images')
            ->setDescription('Migrate base64IMages to url in database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $users = $em->getRepository('TelepayFinancialApiBundle:User')->findAll();
        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();

        $total_users = count($users);
        $total_images = 0;
        //change base64image to file in users
        foreach ($users as $user){
            if($user->getBase64Image() != ''){
                $total_images++;
                $base64_image = $user->getBase64Image();
                if(strpos($base64_image, 'data:image/jpeg;base64') !== false){
                    //is jpg file
                    $base64 = str_replace('data:image/jpeg;base64,', '', $base64_image);
                    $ext = '.jpg';
                }elseif(strpos($base64_image, 'data:image/png;base64') !== false){
                    //is png file
                    $base64 = str_replace('data:image/png;base64,', '', $base64_image);
                    $ext = '.png';
                }elseif(strpos($base64_image, 'data:application/pdf;base64') !== false){
                    //is pdff file
                    $base64 = str_replace('data:application/pdf;base64,', '', $base64_image);
                    $ext = '.pdf';
                }else{
                    $base64 = str_replace('data:application/png;base64,', '', $base64_image);
                    $ext = '.png';
                }

                $name = uniqid('profile_');

                $fs = new Filesystem();
                $fs->dumpFile($this->getContainer()->getParameter('uploads_dir').'/' . $name . $ext, base64_decode($base64));

//                die(print_r('caca',true));
                //add url to user database
                $user->setProfileImage($this->getContainer()->getParameter('files_path') . '/' . $name.$ext);
                $em->flush();
            }
        }

        $output->writeln('TOTAL USERS : '.$total_users);
        $output->writeln('TOTAL IMAGES : '.$total_images);

        $total_companies = count($companies);
        $total_images = 0;
        foreach ($companies as $company){
            if($company->getBase64Image() != ''){
                $total_images++;
                $base64_image = $company->getBase64Image();
                if(strpos($base64_image, 'data:image/jpeg;base64') !== false){
                    //is jpg file
                    $base64 = str_replace('data:image/jpeg;base64,', '', $base64_image);
                    $ext = '.jpg';
                }elseif(strpos($base64_image, 'data:image/png;base64') !== false){
                    //is png file
                    $base64 = str_replace('data:image/png;base64,', '', $base64_image);
                    $ext = '.png';
                }elseif(strpos($base64_image, 'data:application/pdf;base64') !== false){
                    //is pdff file
                    $base64 = str_replace('data:application/pdf;base64,', '', $base64_image);
                    $ext = '.pdf';
                }else{
                    $base64 = str_replace('data:application/png;base64,', '', $base64_image);
                    $ext = '.png';
                }

                $name = uniqid('company_');

                $fs = new Filesystem();
                $fs->dumpFile($this->getContainer()->getParameter('uploads_dir').'/' . $name . $ext, base64_decode($base64));

//                die(print_r('caca',true));
                //add url to user database
                $company->setCompanyImage($this->getContainer()->getParameter('files_path') . '/' . $name.$ext);
                $em->flush();
            }
        }

        $output->writeln('TOTAL GROUPS : '.$total_companies);
        $output->writeln('TOTAL IMAGES : '.$total_images);

    }


}