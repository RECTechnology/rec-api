<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Financial\Currency;

class ExchangePriceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:exchange:price:update')
            ->setDescription('Update all exchange prices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $error = 0;
        $errorBody = array();
        foreach(Currency::$ALL as $inputCurrency){
            foreach(Currency::$ALL as $outputCurrency){
                if($inputCurrency !== $outputCurrency){
                    $providerName = 'net.telepay.ticker.' . $inputCurrency . 'x' . $outputCurrency;
                    try {
                        $provider = $this->getContainer()->get($providerName);
                        if($provider->getInCurrency() === $inputCurrency && $provider->getOutCurrency() === $outputCurrency) {
                            $price = $provider->getPrice() * pow(10, (Currency::$SCALE[$outputCurrency] - Currency::$SCALE[$inputCurrency]));
                            $exchange = new Exchange();
                            $exchange->setSrc($inputCurrency);
                            $exchange->setDst($outputCurrency);
                            $exchange->setPrice($price);
                            $date = new \DateTime();
                            $exchange->setDate($date);
                            $em->persist($exchange);
                            $em->flush();
                            $output->writeln(
                                "SUCCESS: price (" . $inputCurrency . " -> " . $outputCurrency . "): " . $price
                            );
                        }
                        else {
                            //send email
                            $this->sendEmail('Exchange error', "ERROR: Bad exchange, unexpected input or output currencies:".$inputCurrency.'<->'.$outputCurrency);
                            throw new \LogicException("ERROR: Bad exchange, unexpected input or output currencies");
                        }
                    }catch (Exception $e) {
                        //send email
                        $error = 1;
                        $errorBody[] =  $inputCurrency.'<->'.$outputCurrency.' Error Message: '.$e->getMessage();
//                        $this->sendEmail('Fatal Exchange error', $inputCurrency.'<->'.$outputCurrency.' Error Message: '.$e->getMessage());
                        $output->writeln("ERROR: " . $e->getMessage());
                    }
                }

            }
        }

        if($error == 1){
            $this->sendEmail('Fatal Exchange error', $errorBody);
        }


        $output->writeln("FINISHED");
    }

    private function sendEmail($subject, $body){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo(array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }
}