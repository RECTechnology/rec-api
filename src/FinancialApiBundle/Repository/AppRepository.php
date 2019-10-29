<?php


namespace App\FinancialApiBundle\Repository;


use App\FinancialApiBundle\Annotations\TranslatedProperty;
use App\FinancialApiBundle\Entity\Group;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppRepository extends EntityRepository implements ContainerAwareInterface {

    use ContainerAwareTrait;

    /** @var RequestStack $stack */
    private $stack;

    public function __construct(EntityManagerInterface $em, ORM\ClassMetadata $class, RequestStack $stack) {
        parent::__construct($em, $class);
        $this->stack = $stack;
    }

    /**
     * @return string|null
     */
    protected function getRequestLocale(){
        $request = $this->getRequest();
        $method = $request->getMethod();
        $headers = $request->headers;
        if(in_array($method, ['POST', 'PUT']) && $headers->has('content-language')){
            return $headers->get('content-language');
        }
        return $headers->get('accept-language', $request->getLocale());
    }

    /**
     * @return Request
     */
    protected function getRequest(){
        return $this->stack->getCurrentRequest();
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function show($id) {
        return $this->find($id);
    }

    /**
     * @param Request $request
     * @param $search
     * @param $limit
     * @param $offset
     * @param $order
     * @param $sort
     * @return array
     * @throws NonUniqueResultException
     */
    public function index(Request $request, $search, $limit, $offset, $order, $sort){

        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata($this->getClassName());
        $properties = $metadata->getFieldNames();

        try {
            $rc = new \ReflectionClass($this->getClassName());
            foreach($rc->getProperties() as $rp){
                $ar = new AnnotationReader();
                foreach ($ar->getPropertyAnnotations($rp) as $an){
                    if($an instanceof TranslatedProperty){
                        if($sort == $rp->name){
                            $translatedProperty = $rp->name . '_' . $request->getLocale();
                            if(in_array($translatedProperty, $properties)){
                                $sort = $translatedProperty;
                            }
                        }
                    }
                }
            }
        } catch (\ReflectionException $e) {
            throw new HttpException(400, "Invalid parameters");
        } catch (AnnotationException $e) {
            throw new HttpException(400, "Invalid parameters");
        }

        if(!in_array($sort, $properties))
            throw new HttpException(400, "Invalid sort: it must be a valid property (counters and virtual properties are not allowed)");

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $className = $this->getClassName();

        $trueExpr = $qb->expr()->eq($qb->expr()->literal(1), $qb->expr()->literal(1));

        # Key-Value filter
        $kvFilter = $qb->expr()->andX();
        foreach ($request->query->keys() as $key){
            if(substr($key, -3) === "_id") {
                $name = substr($key, 0, strlen($key) - 3);
                if(!property_exists($className, $name)) {
                    throw new HttpException(400, "Invalid parameter '$key'");
                }
                try {
                    $rc = new \ReflectionClass($className);
                    $rp = $rc->getProperty($name);
                    $ar = new AnnotationReader();
                    if(!$ar->getPropertyAnnotation($rp, ORM\ManyToOne::class) &&
                        !$ar->getPropertyAnnotation($rp, ORM\OneToOne::class)) {
                        throw new HttpException(
                            400,
                            "Invalid parameter '$key', _id endings is for related entities only"
                        );
                    }
                } catch (\ReflectionException $e) {
                    throw new HttpException(400, "Invalid parameter '$key'", $e);
                } catch (AnnotationException $e) {
                    throw new HttpException(400, "Invalid parameter '$key'", $e);
                }
                $kvFilter->add($qb->expr()->eq('IDENTITY(e.' . $name . ')', $request->query->get($key)));
            }
            elseif(property_exists($className, $key)){
                try {
                    $rc = new \ReflectionClass($className);
                    $rp = $rc->getProperty($key);
                    $ar = new AnnotationReader();
                    $relationships = [
                        'single' => [ORM\ManyToOne::class, ORM\OneToOne::class],
                        'multiple' => [ORM\ManyToMany::class, ORM\OneToMany::class]
                    ];

                    $isRelationship = false;
                    foreach ($relationships as $type => $rels){
                        foreach ($rels as $relationship){
                            $an = $ar->getPropertyAnnotation($rp, $relationship);
                            if($an){
                                if(!is_numeric($request->query->get($key)))
                                    throw new HttpException(400, "Invalid parameter '$key', it must be an ID");
                                switch ($type){
                                    case 'single':
                                        $kvFilter->add($qb->expr()->eq('IDENTITY(e.' . $key . ')', $request->query->get($key)));
                                        break;
                                    case 'multiple':
                                        $em = $this->getEntityManager();
                                        $targetRepo = $em->getRepository($an->targetEntity);
                                        $rel = $targetRepo->find($request->query->get($key));
                                        if($rel) $kvFilter->add($qb->expr()->isMemberOf($rel, 'e.' . $key));
                                        break;
                                }
                                $isRelationship = true;
                                break;
                            }
                        }
                        if($isRelationship) break;
                    }
                    if(!$isRelationship)
                        $kvFilter->add($qb->expr()->eq('e.' . $key, "'" . $request->query->get($key) . "'"));
                } catch (\ReflectionException $e) {
                    throw new HttpException(400, "Invalid parameter '$key'", $e);
                } catch (AnnotationException $e) {
                    throw new HttpException(400, "Invalid parameter '$key'", $e);
                }
            }
        }
        # Adding always-true expression to avoid kvFilter to be empty
        if($kvFilter->count() <= 0) $kvFilter->add($trueExpr);

        # Search filter
        $searchFilter = $qb->expr()->orX();
        if($search !== "") {
            foreach ($properties as $property) {
                $searchFilter->add(
                    $qb->expr()->like(
                        'e.' . $property,
                        $qb->expr()->literal('%' . $search . '%')
                    )
                );
            }
        }
        # Adding always-true expression to avoid searchFilter to be empty
        if($kvFilter->count() <= 0) $searchFilter->add($trueExpr);

        $where = $qb->expr()->andX();
        $where->add($kvFilter);
        $where->add($searchFilter);

        $qb = $qb->from($className, 'e');
        $qb = $qb->where($where);
        //die($qb->getDQL());
        $qTotal = $qb
            ->select('count(e.id)')
            ->getQuery();

        $qResult = $qb
            ->select('e')
            ->orderBy('e.' . $sort, $order)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();
        //->getResult();

        return [intval($qTotal->getSingleScalarResult()), $qResult->getResult()];
    }

}