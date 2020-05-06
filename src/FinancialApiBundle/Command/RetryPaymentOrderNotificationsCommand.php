<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\PaymentOrderNotification;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\App\Commons\FeeDeal;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitAdder;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\Notificator;
use App\FinancialApiBundle\Document\Transaction;

/**
 * Class RetryPaymentOrderNotificationsCommand
 * @package App\FinancialApiBundle\Command
 */
class RetryPaymentOrderNotificationsCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:pos:notifications:retry')
            ->setDescription('Retry all failed POS notifications')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'limit the notifications to send (default: 10)'
            )
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Listing order notifications ...');
        $limit = intval($input->getArgument('limit'));
        if(!$limit) $limit = 1000;

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(PaymentOrderNotification::class);

        $notifications = $repo->findBy(
            ["status" => 'retrying'],
            ['created_at' => 'ASC'], # older goes first
            $limit
        );

        $notifier = $this->getContainer()->get(Notifier::class);

        /** @var PaymentOrderNotification $notification */
        foreach($notifications as $notification){
            $notifier->send(
                $notification,
                function($ignored) use ($notification) {
                    $notification->setStatus(PaymentOrderNotification::STATUS_NOTIFIED);
                },
                function($ignored) use ($notification) {
                    $notification->setTries($notification->getTries() + 1);
                },
                function() use ($notification, $em) {
                    $em->flush();
                }
            );
        }

    }

}