<?php
namespace App\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use App\FinancialApiBundle\Entity\Exchange;
use App\FinancialApiBundle\Financial\Currency;

class ExchangePriceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:exchange:price:update')
            ->setDescription('Update all exchange prices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $error = 0;
        $errorBody = array();
        $currency_list = Currency::$TICKER_LIST;
        foreach($currency_list as $inputCurrency){
            $output->writeln("IN: " . $inputCurrency);
            foreach($currency_list as $outputCurrency){
                $output->writeln("OUT: " . $outputCurrency);
                if($inputCurrency !== $outputCurrency){
                    $output->writeln("in != out");
                    $providerName = 'net.app.ticker.' . $inputCurrency . 'x' . $outputCurrency;
                    try {
                        $provider = $this->getContainer()->get($providerName);
                        $output->writeln($provider->getInCurrency() . " " . $inputCurrency . " " . $provider->getOutCurrency() . " " . $outputCurrency);
                        if($provider->getInCurrency() === $inputCurrency && $provider->getOutCurrency() === $outputCurrency) {
                            $price = $provider->getPrice() * pow(10, (Currency::$SCALE[$outputCurrency] - Currency::$SCALE[$inputCurrency]));
                            if($price>0) {
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
                        }
                        else {
                            $output->writeln("ELSE");
                            //send email
                            $error = 1;
                            $errorBody[] = "ERROR: Bad exchange, unexpected input or output currencies:".$inputCurrency.'<->'.$outputCurrency;
//                            $this->sendEmail('Exchange error', "ERROR: Bad exchange, unexpected input or output currencies:".$inputCurrency.'<->'.$outputCurrency);
//                            throw new \LogicException("ERROR: Bad exchange, unexpected input or output currencies");
                        }
                    }catch (Exception $e) {
                        $output->writeln("CATCH");
                        //check the last exchange price
                        $last_exchange = $em->getRepository('FinancialApiBundle:Exchange')->findBy(
                            array(
                                'src'   =>  $inputCurrency,
                                'dst'   =>  $outputCurrency
                            ),
                            array(
                                'id' =>  'DESC'
                            ),
                            1);
                        //send email on if more than 5 minutes without price
                        $now = new \DateTime();
                        $output->writeln("CHECKING TTL");
                        if($last_exchange[0]->getDate()->getTimestamp() + 300 < $now->getTimestamp()){
                            $output->writeln("ERROR = 1");
                            $error = 1;
                            $errorBody[] =  $inputCurrency.'<->'.$outputCurrency.' Error Message: '.$e->getMessage();
//                        $this->sendEmail('Fatal Exchange error', $inputCurrency.'<->'.$outputCurrency.' Error Message: '.$e->getMessage());
                            $output->writeln("ERROR: " . $e->getMessage());
                        }

                    }
                }

            }
        }

        if($error == 1){
            $output->writeln("SENDING ERROR EMAIL");
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
                    ->render('FinancialApiBundle:Email:exchange_error.html.twig',
                        array(
                            'messages'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }
}