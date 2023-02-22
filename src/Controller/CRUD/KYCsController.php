<?php

namespace App\Controller\CRUD;

use App\Controller\Transactions\IncomingController2;
use App\Entity\Activity;
use App\Entity\Campaign;
use App\Entity\DelegatedChangeData;
use App\Entity\KYC;
use App\Entity\Mailing;
use App\Entity\MailingDelivery;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Exception\AppException;
use App\Financial\Driver\LemonWayInterface;
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
use App\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Offer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Stubs\DocumentManager;
use App\Document\Transaction;

/**
 * Class KYCsController
 * @package App\Controller\CRUD
 */
class KYCsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_UPDATE] = self::ROLE_USER;
        return $grants;
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
        /** @var KYC $kyc */
        $kyc = $this->findObject($id);

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');
        $user = $tokenStorage->getToken()->getUser();

        if(!$this->userCanUpdateKyc($user, $kyc)){
            throw new HttpException(403, "Insufficient permissions to UPDATE this resource");
        }

        //TODO check params
        $this->checkParams($request);

        return parent::updateAction($request, $role, $id);
    }

    private function userCanUpdateKyc(User $user, KYC $kyc){
        $owner = $kyc->getUser();

        return $owner->getId() === $user->getId();

    }

    private function checkParams(Request $request){
        $protectedFields = [
            "full_name_validated",
            "email_validated",
            "validation_phone_code",
            "phone_validated",
            "dateBirth_validated",
            "document_front_status",
            "document_rear_status",
            "document_validated",
            "country_validated",
            "address_validated",
            "proof_of_residence",
            "tier1_status",
            "tier2_status",
            "tier1_status_request",
            "tier2_status_request",
        ];

        $params = $request->request->all();
        foreach ($params as $param=>$value){
            if(in_array($param,$protectedFields)){
                throw new HttpException(403, $param.' can not be accessed');
            }
        }
    }

}
