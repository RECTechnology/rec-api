<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Exception\AppException;
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
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class LemonWayAccountsController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class LemonWayAccountsController extends AccountsController {


    public function lemonwayReadAction(Request $request, $role, $id) {
        $this->checkPermissions($role, self::CRUD_SHOW);
        /** @var Group $account */
        $account = $this->findObject($id);
        $lw = $this->container->get('net.app.driver.lemonway.eur');

        $resp = $lw->callService(
            'GetWalletDetails',
            ["wallet" => $account->getCif()]
        );
        if(is_array($resp) || $resp->E != null)
            throw new AppException(503, "LW error", (array) $resp);
        $wallet = json_decode(json_encode($resp->WALLET), true);
        return $this->restV2(
            200,
            "ok",
            "LW info fetched successfully",
            $wallet
        );
    }

    public function lemonwaySendToAccountAction(Request $request, $role, $id) {
        $amount = $request->request->get('amount');
        $to = $request->request->get('to');
        $from = $id;
        return $this->sendBetweenWallets($role, $from, $to, $amount);
    }

    public function lemonwaySendFromAccountAction(Request $request, $role, $id) {
        $amount = $request->request->get('amount');
        $from = $request->request->get('from');
        $to = $id;
        return $this->sendBetweenWallets($role, $from, $to, $amount);
    }


    private function sendBetweenWallets($role, $from, $to, $amount) {

        if(!$amount) throw new HttpException(400, "Param 'amount' is required");
        if(!$to) throw new HttpException(400, "Param 'to' is required");
        if(!$from) throw new HttpException(400, "Param 'from' is required");

        $this->checkPermissions($role, self::CRUD_UPDATE);
        /** @var Group $src */
        $src = $this->findObject($from);
        /** @var Group $dst */
        $dst = $this->findObject($to);

        if(!$src || !$dst) throw new HttpException(404, "Source or Destination accounts not found");

        $lw = $this->container->get('net.app.driver.lemonway.eur');

        $resp = $lw->callService(
            'SendPayment',
            [
                "debitWallet" => $src->getCif(),
                "creditWallet" => $dst->getCif(),
                "amount" => $amount
            ]
        );
        if(is_array($resp) || $resp->E != null)
            throw new AppException(503, "LW error", (array) $resp);
        $wallet = json_decode(json_encode($resp->WALLET), true);
        return $this->restV2(
            200,
            "ok",
            "LW tx success",
            $wallet
        );
    }
}
