<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/10/14
 * Time: 4:05 PM
 */

namespace Arbaf\FinancialApiBundle\Security;

use FOS\UserBundle\Security\UserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiUserProvider extends  UserProvider{

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

    public function refreshUser(UserInterface $user) {
        throw new UnsupportedUserException();
    }
}
