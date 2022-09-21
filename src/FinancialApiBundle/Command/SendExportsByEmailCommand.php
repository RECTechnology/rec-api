<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\FinancialApiBundle\Command;


use App\FinancialApiBundle\Controller\SecurityTrait;
use App\FinancialApiBundle\Entity\EmailExport;
use DateTimeZone;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendExportsByEmailCommand extends ContainerAwareCommand
{

    use SecurityTrait;
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('rec:exports:send')
            ->setDescription('Send pending exports by email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->container->get('doctrine')->getManager();

        //get pending exports
        $pendingExports = $em->getRepository(EmailExport::class)->findBy(array("status" => EmailExport::STATUS_CREATED));

        /** @var EmailExport $export */
        foreach ($pendingExports as $export){
            //search in database by entity
            $entityName = $export->getEntityName();
            if(class_exists('App\\FinancialApiBundle\\Entity\\' . $entityName)){
                $repo = $em->getRepository('App\\FinancialApiBundle\\Entity\\' . $entityName);
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
                    $this->sendEmail($export->getEmail(), "Exportacion ". $entityName, $file, $dwFilename );
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
                if(count($found) == 0)
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

    private function sendEmail($email, $subject, $file, $fileName){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo(array($email))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('FinancialApiBundle:Email:empty_email.html.twig',
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
            )
            ->setContentType('text/html');

        $message->attach(\Swift_Attachment::newInstance($file, $fileName));

        $this->getContainer()->get('mailer')->send($message);
    }


}