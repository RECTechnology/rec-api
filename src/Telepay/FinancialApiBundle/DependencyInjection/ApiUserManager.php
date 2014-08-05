<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/30/14
 * Time: 10:27 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class ApiUserManager{

    private $entityManager;
    private $userRepo;
    private $role;

    public function __construct($controller, $role){
        $this->role = $role;
        $this->entityManager = $controller->getDoctrine();
        $this->userRepo = $this->entityManager
            ->getRepository("TelepayFinancialApiBundle:User");
    }

    public function getAll($limit = 10, $offset = 0){
        $users = $this->userRepo->findBy(array(), null, $limit, $offset);
        $validUsers = array();
        //$debug="";
        foreach($users as $user){
            foreach($user->getRoles() as $role){
                if($this->role === $role){
                    $validUsers[]=$user;
                    //$debug .= print_r($user->getRoles(), true)."->".$user->getUsername()."/";
                    break;
                }
            }
        }
        //if($this->role == 'ROLE_USER') die(print_r($debug, true));
        return $validUsers;
    }


    public function getBy($criteria, $limit = 10, $offset = 0){
        $users = $this->userRepo->findBy($criteria, null, $limit, $offset);
        $validUsers = array();
        foreach($users as $user){
            foreach($user->getRoles() as $role){
                if($this->role === $role){
                    $validUsers[]=$user;
                    break;
                }
            }
        }
        return $validUsers;
    }

    public function getOneBy($criteria){
        $user = $this->userRepo->findOneBy($criteria);
        foreach($user->getRoles() as $role){
            if($this->role === $role) return $user;
        }
        throw new UsernameNotFoundException("User not found");
    }
}