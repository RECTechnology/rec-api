<?php


namespace App\FinancialApiBundle\Repository;


use App\FinancialApiBundle\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppRepository extends EntityRepository {

    /** @var RequestStack $stack */
    private $stack;

    public function __construct(EntityManagerInterface $em, Mapping\ClassMetadata $class, RequestStack $stack) {
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
     * @param mixed $id
     * @param null $lockMode
     * @param null $lockVersion
     * @return mixed|object|null
     */
    public function find($id, $lockMode = null, $lockVersion = null) {
        $q = $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery();
        $q = $this->setTranslatableHints($q);
        try {
            return $q->getSingleResult();
        } catch (NoResultException $ignored) {
        } catch (NonUniqueResultException $ignored) {
        }
        return null;
    }

    /**
     * @param Query $q
     * @return Query
     */
    private function setTranslatableHints(Query $q){
        $q->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            TranslationWalker::class
        );

        $q->setHint(
            TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $this->getRequest()->getLocale() // take locale from session or request etc.
        );

        $q->setHint(
            TranslatableListener::HINT_FALLBACK,
            true
        );

        return $q;
    }


    /**
     * @param $request
     * @param $search
     * @param $limit
     * @param $offset
     * @param $order
     * @param $sort
     * @return array
     * @throws NonUniqueResultException
     */
    public function index($request, $search, $limit, $offset, $order, $sort){
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $properties = $em->getClassMetadata($this->getClassName())->getFieldNames();
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
                    throw new HttpException(400, "Bad parameter '$key'");
                }
                $kvFilter->add($qb->expr()->eq('IDENTITY(e.' . $name . ')', $request->query->get($key)));
            }
            elseif(property_exists($className, $key)){
                $kvFilter->add($qb->expr()->eq('e.' . $key, "'" . $request->query->get($key) . "'"));
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

        $qResult = $this->setTranslatableHints($qResult);

        return [intval($qTotal->getSingleScalarResult()), $qResult->getResult()];
    }

}