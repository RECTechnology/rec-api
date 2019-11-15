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
            throw new AppException(404, "LW wallet not found");
        $wallet = json_decode(json_encode($resp->WALLET), true);
        return $this->restV2(
            200,
            "ok",
            "LW info fetched successfully",
            $wallet
        );
    }
}
