<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\Command;


use App\Controller\SecurityTrait;
use App\DependencyInjection\Commons\MailerAwareTrait;
use App\Entity\EmailExport;
use DateTimeZone;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\Email;

class SendExportsByEmailCommand extends SynchronizedContainerAwareCommand
{

    use SecurityTrait;
    use MailerAwareTrait;


    protected function configure()
    {
        $this
            ->setName('rec:exports:send')
            ->setDescription('Send pending exports by email')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $output->writeln("Send Exports Command: START");

        $em = $this->container->get('doctrine.orm.entity_manager');

        //get pending exports
        $pendingExports = $em->getRepository(EmailExport::class)->findBy(array("status" => EmailExport::STATUS_CREATED));

        /** @var EmailExport $export */
        foreach ($pendingExports as $export){
            //search in database by entity
            $entityName = $export->getEntityName();
            if(class_exists('App\\Entity\\' . $entityName)){
                $repo = $em->getRepository('App\\Entity\\' . $entityName);
                $request = new Request();
                $request->query->add($export->getQuery());

                $limit = $request->query->get('limit', 10);
                $offset = $request->query->get('offset', 0);
                if($offset < 0) $offset = 0;
                $sort = $request->query->get('sort', "id");
                $order = strtoupper($request->query->get('order', "DESC"));
                $search = $request->query->get('search', "");

                [$total, $result] = $repo->index($request, $search, $limit, $offset, $order, $sort);

                $elems = $this->secureOutputFromCommand($result);
                $now = new \DateTime("now", new DateTimeZone('Europe/Madrid'));
                $dwFilename = "export-" .  $entityName . "s-" . $now->format('Y-m-d\TH-i-sO') . ".csv";
                try{
                    $file = $this->prepareCsv($elems, $export->getFieldMap(), $dwFilename);
                    $this->sendEmail($export->getEmail(), "Exportacion ". $entityName, $dwFilename );
                    $export->setStatus(EmailExport::STATUS_SUCCESS);
                    $em->flush();
                }catch (HttpException $e){
                    $output->writeln("Error preparing csv");
                    $export->setLastError($e->getMessage());
                    $export->setStatus(EmailExport::STATUS_FAILED);
                }

            }else{
                $export->setStatus(EmailExport::STATUS_ERROR);
            }

        }
        $em->flush();
        $output->writeln("Send Exports Command: END");
    }

    private function prepareCsv($elems, $fieldMap, $dwFilename){
        $fs = new Filesystem();
        $tmpFilename = "/tmp/$dwFilename";
        $fs->touch($tmpFilename);
        $fp = fopen($tmpFilename, 'w');

        $export = [array_keys($fieldMap)];
        foreach($elems as $el){

            try {
                $obj = new JsonObject($el);
            } catch (InvalidJsonException $e) {
                throw new HttpException(400, "Invalid JSON: " . $e->getMessage(), $e);
            }
            $exportRow = [];
            foreach($fieldMap as $jsonPath){
                try {
                    $found = $obj->get($jsonPath);
                } catch (Exception $e) {
                    throw new HttpException(400, "Invalid JsonPath: " . $e->getMessage(), $e);
                }
                if(!$found)
                    $exportRow []= null;
                elseif(count($found) == 1) {
                    if(is_array($found[0])) {
                        throw new HttpException(
                            400,
                            "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($found[0])
                        );
                    }
                    $exportRow []= $found[0];
                }
                else {
                    foreach($found as $v){
                        if(is_array($v)){
                            throw new HttpException(
                                400,
                                "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($v)
                            );
                        }
                    }
                    $exportRow []= implode("|", $found);
                }
            }
            $export []= $exportRow;
        }

        foreach($export as $row){
            fputcsv($fp, $row, ";");
        }

        return $fp;

    }

    private function sendEmail($email, $subject, $fileName){

        $no_replay = $this->container->getParameter('no_reply_email');

        $message = (new Email())
            ->subject($subject)
            ->from($no_replay)
            ->to($email)
            ->html(
                $this->container->get('templating')
                    ->render('Email/empty_email.html.twig',
                        array(
                            'mail' => [
                                'subject' => $subject,
                                'body' => "Archivo exportado",
                                'lang' => "es"
                            ],
                            'app' => [
                                'landing' => 'rec.barcelona'
                            ]
                        )
                    )
            );

        $message->attach(file_get_contents('/tmp/'.$fileName), $fileName);

        $this->mailer->send($message);

    }


}