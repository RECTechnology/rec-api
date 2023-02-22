<?php

namespace App\Controller\CRUD;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Exception\AppException;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use JsonPath\JsonObject;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Offer;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class GatewayController
 * @package App\Controller\CRUD
 */
class GatewayController extends CRUDController {

    const PROVIDER_MAP = ['lemonway'];

    public function gatewayAction(Request $request, $role, $provider, $function){
        $this->checkPermissions($role, self::CRUD_UPDATE);
        if(!in_array($provider, self::PROVIDER_MAP))
            throw new HttpException(400, "Invalid provider");
        return $this->$provider($function, $request->request->all());
    }

    public function lemonway($function, $parameters){
        $lw = $this->container->get('net.app.driver.lemonway.eur');
        $resp = $lw->callService($function, $parameters);

        if(is_array($resp))
            throw new AppException(400, "LW error", $resp);
        if($resp->E != null)
            throw new AppException(400, "LW error: {$resp->E}");
        $resp = json_decode(json_encode($resp), true);
        return $this->rest(
            200,
            "ok",
            "Success operation",
            $resp
        );
    }
}
