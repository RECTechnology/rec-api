<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\Entity\Activity;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use App\FinancialApiBundle\Entity\TransactionBlockLog;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\Offer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Stubs\DocumentManager;
use App\FinancialApiBundle\Document\Transaction;

/**
 * Class KYCsController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class TxBlockController extends CRUDController {


    /**
     * @param Request $request
     * @param $role
     * @return Response
     * @throws AnnotationException
     */
    public function createAction(Request $request, $role): Response
    {
        $resp = parent::createAction($request, $role);
        if ($resp->getStatusCode() == 201){
            $dc_id = json_decode($resp->getContent())->data->id;
            /** @var DelegatedChange $dc */
            $dc = $this->findObject($dc_id);
            $log_handler = $this->container->get('net.app.commons.tx_block_log_handler');
            $log_handler->persistLog($dc, TransactionBlockLog::TYPE_DEBUG, 'New empty transaction block');
        }
        return $resp;
    }

    /**
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     * @throws AnnotationException
     */
    public function updateAction(Request $request, $role, $id)
    {
        if ($request->request->has("status")) {
            /** @var DelegatedChange $dc */
            $dc = $this->findObject($id);
            $oldStatus = $dc->getStatus();
            $newStatus = $request->request->get("status");
        }
        $resp = parent::updateAction($request, $role, $id);
        if (isset($dc) && $resp->getStatusCode() == 200) {
            $this->saveTxBlockLog($dc, $oldStatus, $newStatus);
        }
        return $resp;
    }

    /**
     * @param DelegatedChange $dc
     * @param string $oldStatus
     * @param string $newStatus
     */
    private function saveTxBlockLog(DelegatedChange $dc, string $oldStatus, string $newStatus): void
    {
        $log_handler = $this->container->get('net.app.commons.tx_block_log_handler');
        if ($oldStatus == DelegatedChange::STATUS_DRAFT && $newStatus == DelegatedChange::STATUS_SCHEDULED) {
            $log_text = sprintf('From %s to %s. Block txs activated. Scheduled to run at %s',
                DelegatedChange::STATUS_DRAFT,
                DelegatedChange::STATUS_SCHEDULED,
                $dc->getScheduledAt()->format('Y-m-d H:i:s')
            );
        }

        if ($oldStatus == DelegatedChange::STATUS_SCHEDULED && $newStatus == DelegatedChange::STATUS_DRAFT) {
            $log_text = sprintf('From %s to %s. Txs block disabled',
                DelegatedChange::STATUS_SCHEDULED,
                DelegatedChange::STATUS_DRAFT
            );
        }

        if ($oldStatus == DelegatedChange::STATUS_FAILED && $newStatus == DelegatedChange::STATUS_INCOMPLETE) {
            $log_text = sprintf('From %s to %s. Block of transactions not completed. Statistics %s',
                DelegatedChange::STATUS_FAILED,
                DelegatedChange::STATUS_INCOMPLETE,
                json_encode($dc->getStatistics())
            );
        }

        if ($oldStatus == DelegatedChange::STATUS_FAILED && $newStatus == DelegatedChange::STATUS_SCHEDULED) {
            $log_text = sprintf('From %s to %s. Retrying sending since the transaction that caused the failure...',
                DelegatedChange::STATUS_FAILED,
                DelegatedChange::STATUS_SCHEDULED
            );
        }

        if(isset($log_text)) $log_handler->persistLog($dc, TransactionBlockLog::TYPE_DEBUG, $log_text);
    }
}
