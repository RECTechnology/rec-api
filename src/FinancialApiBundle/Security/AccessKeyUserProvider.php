<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/10/14
 * Time: 4:05 PM
 */

namespace App\FinancialApiBundle\Security;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Security\UserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class AccessKeyUserProvider extends  UserProvider{

    protected $entityManager;

    public function setEntityManager(ObjectManager $em){
        $this->entityManager = $em;
    }

    public function loadUserByAccessKey($accessKey)
    {
        // Look up the username based on the token in the database, via
        // an API call, or do something entirely different
        $user = $this->userManager->findUserBy(array('access_key' => $accessKey));
        if(!$user){
            throw new UsernameNotFoundException(sprintf("User with access_key '%s' not found", $accessKey));
        }
        return $user;
    }


    public function loadUserByUsername($username)
    {
        return $this->userManager->findUserByUsername($username);
    }

    public function loadUserByUsernameOrEmail($usernameOrEmail)
    {
        return $this->userManager->findUserByUsernameOrEmail($usernameOrEmail);
    }



    public function loadUserById($id) {
        $user = $this->userManager->findUserBy(array('id' => $id));
        if(!$user){
            throw new UsernameNotFoundException(sprintf("User with id '%s' not found", $id));
        }
        return $user;
    }


    public function updatePassword($user) {
        $this->userManager->updatePassword($user);
    }


    public function refreshUser(UserInterface $user) {
        throw new UnsupportedUserException();
    }
}
