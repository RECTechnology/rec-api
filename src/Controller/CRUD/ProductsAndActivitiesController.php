<?php

namespace App\Controller\CRUD;

use App\Entity\User;
use App\Entity\UserGroup;
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
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ProductsAndActivitiesController
 * @package App\Controller\CRUD
 */
class ProductsAndActivitiesController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_SEARCH] = self::ROLE_USER;
        $grants[self::CRUD_INDEX] = self::ROLE_USER;
        $grants[self::CRUD_SHOW] = self::ROLE_USER;
        $grants[self::CRUD_CREATE] = self::ROLE_USER;
        return $grants;
    }
}
