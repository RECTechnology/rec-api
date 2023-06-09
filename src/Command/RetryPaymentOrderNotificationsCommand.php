<?php
namespace App\Command;

use App\DependencyInjection\Commons\Notifier;
use App\Entity\PaymentOrderNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RetryPaymentOrderNotificationsCommand
 * @package App\Command
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
                'limit the notifications to send (default: 1000)'
            )
        ;
    }

    /**
     * @required
     * @param Notifier $notifier
     */
    public function setNotifier(Notifier $notifier){
        $this->notifier = $notifier;
    }

    private $orderNotificationsFailed = [];

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Listing order notifications ...');
        $limit = intval($input->getArgument('limit'));
        if(!$limit) $limit = 1000;

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(PaymentOrderNotification::class);

        $notifications = $repo->findBy(
            ["status" => PaymentOrderNotification::STATUS_RETRYING],
            ['created' => 'ASC'], # older goes first
            $limit
        );

        $output->writeln(count($notifications).' notifications found');

        /** @var PaymentOrderNotification $notification */
        foreach($notifications as $notification){
            $order = $notification->getPaymentOrder();
            $output->writeln('Notifying order: '.$order->getId());

            //each order can have many notifications, and if one fails, the rest should not be tried
            if(!in_array($order, $this->orderNotificationsFailed)){

                //we need to recalculate signature and add a current nonce
                $nonce = round(microtime(true) * 1000, 0);

                $content = $notification->getContent();

                unset($content['signature'], $content['nonce']);

                $content['nonce'] = $nonce;

                ksort($content);
                $signaturePack = json_encode($content, JSON_UNESCAPED_SLASHES);

                $signature = hash_hmac('sha256', $signaturePack, base64_decode($order->getPos()->getAccessSecret()));

                $notification->setContent($content + ["signature" => $signature]);

                $em->flush();

                $this->notifier->send(
                    $notification,
                    function($ignored) use ($notification) {
                        $notification->setStatus(PaymentOrderNotification::STATUS_NOTIFIED);
                    },
                    function($ignored) use ($notification, $order, $output) {
                        $this->orderNotificationsFailed []= $order;
                        $output->writeln('FAILED');
                        $tries = $notification->getTries() + 1;
                        $notification->setTries($tries);
                        $now = new \DateTime();
                        $diff = $now->getTimestamp() - $notification->getCreated()->getTimestamp();
                        if($diff > PaymentOrderNotification::EXPIRE_TIME)
                            $notification->setStatus(PaymentOrderNotification::STATUS_EXPIRED);

                    },
                    function() use ($notification, $em) {
                        $em->flush();
                    }
                );
            }
        }

    }

}