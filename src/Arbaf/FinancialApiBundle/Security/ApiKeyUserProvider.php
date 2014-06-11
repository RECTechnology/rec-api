<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/10/14
 * Time: 4:05 PM
 */

namespace Arbaf\FinancialApiBundle\Security;

use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Security\UserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiKeyUserProvider extends  UserProvider{

    public function getUsernameForAccessKey($accessKey)
    {
        // Look up the username based on the token in the database, via
        // an API call, or do something entirely different
        $user = $this->userManager->findUserBy(array('id' => $accessKey));
        if(!$user){
            throw new UsernameNotFoundException(sprintf("User with id '%s' not found", $accessKey));
        }
        return $user->getUsername();
    }

    public function refreshUser(UserInterface $user) {
        throw new UnsupportedUserException();
    }
}
