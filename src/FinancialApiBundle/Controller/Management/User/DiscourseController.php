<?php

namespace App\FinancialApiBundle\Controller\Management\User;

use App\FinancialApiBundle\Controller\Management\Admin\UsersController;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Client as OAuthClient;
use App\FinancialApiBundle\Entity\Document;
use App\FinancialApiBundle\Entity\DocumentKind;
use App\FinancialApiBundle\Entity\SmsTemplates;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\UsersSmsLogs;
use App\FinancialApiBundle\Exception\AppException;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Response;
use App\FinancialApiBundle\Entity\CashInTokens;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\ServiceFee;
use App\FinancialApiBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;
use App\FinancialApiBundle\Controller\Google2FA;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use App\FinancialApiBundle\Controller\SecurityTrait;

class DiscourseController extends RestApiController {

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function bridgeDiscourseAction(Request $request, $discourse_endpoint){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        /** @var Group $account */
        $account = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        if(!$account->getActive()) throw new AppException(412, "Default account is not active");

        //check if account is b2b_rezero
        if($account->getRezeroB2bAccess() !== Group::ACCESS_STATE_GRANTED) throw new HttpException(403, 'Account not granted');

        if(!$account->getRezeroB2bApiKey() || !$account->getRezeroB2bUserId()) throw new HttpException(403, 'Account not configured yet');

        $params = $request->request->all();
        $urlParams = $request->query->all();
        //TODO call discourse
        /** @var DiscourseApiManager $discourseManager */
        $discourseManager = $this->container->get('net.app.commons.discourse.api_manager');
        try{
            $discourseResponse = $discourseManager->bridgeCall($account, $discourse_endpoint, $request->getMethod(), $params, $urlParams);
        }catch (HttpException $e){
            throw new HttpException($e->getStatusCode(), $e->getMessage());
        }catch (\Exception $e){
            throw new HttpException(500, $e->getMessage());
        }

        switch ($request->getMethod()){
            case 'PUT':
                $statusCode = 204;
                break;
            case 'POST':
                $statusCode = 201;
                break;
            default:
                $statusCode = 200;
                break;
        }

        return $this->restV2($statusCode, 'success', 'Request successful', $discourseResponse);
    }

}
