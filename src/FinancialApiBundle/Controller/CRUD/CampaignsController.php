<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\DataFixture\ActivityFixture;
use App\FinancialApiBundle\Entity\AccountCampaign;
use App\FinancialApiBundle\Entity\Activity;
use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use App\FinancialApiBundle\Entity\SmsTemplates;
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
use App\FinancialApiBundle\Controller\Management\Admin\UsersController;

/**
 * Class CampaignsController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class CampaignsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_INDEX] = self::ROLE_PUBLIC;
        return $grants;
    }

    public function indexAction(Request $request, $role)
    {
        return parent::indexAction($request, $role);
    }

    public function deleteAction($role, $id)
    {
        //check if this campaign has joined in accounts
        $em = $this->get('doctrine')->getEntityManager();
        $account_campaigns = $em->getRepository(AccountCampaign::class)->findBy(array('campaign' => $id));
        $campaign = $em->getRepository(Campaign::class)->find($id);
        $isV1Campaign = $campaign->getVersion() < 2;

        if($account_campaigns) throw new HttpException(403, 'Campaign with joined in users can not be removed');
        if($isV1Campaign) throw new HttpException(403, 'Campaigns V1 can not be removed');

        return parent::deleteAction($role, $id); // TODO: Change the autogenerated stub
    }

    public function searchAction(Request $request, $role)
    {
        if($request->query->has('statuses')){
            $response = parent::searchAction($request, $role);
            $content = json_decode($response->getContent(),true);
            $status_array = $request->query->get('statuses');
            $campaigns = [];
            foreach ($content['data']['elements'] as $element){
                if(in_array($element['status'], $status_array)){
                    $campaigns[] = $element;
                }
            }
            return $this->restV2(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                array(
                    'total' => count($campaigns),
                    'elements' => $campaigns
                )
            );
        }

        return parent::searchAction($request, $role);
    }

}
