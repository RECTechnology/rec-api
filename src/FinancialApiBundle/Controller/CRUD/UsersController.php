<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UsersController extends CRUDController
{
    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_DELETE => self::ROLE_USER,
        ];
    }

    public function deleteAction($role, $id)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->get('doctrine')->getEntityManager();
        /** @var User $find_user */
        $find_user = $em->getRepository(User::class)->find($id);

        if(!$find_user) throw new HttpException(404, 'User not found');

        /** @var User $current_user */
        $current_user = $this->getUser();

        if($find_user->getId() !== $current_user->getId()) throw new HttpException(403, 'You do not have the necessary permissions ');

        $this->sendEmail($current_user);

        return $this->restV2(204, 'OK', 'User delete request has been sent');
    }

    private function sendEmail(User $user){
        $from = $this->getParameter('no_reply_email');
        $to = $this->getParameter('resume_admin_emails_list');
        $template = 'FinancialApiBundle:Email:empty_email.html.twig';
        $message = \Swift_Message::newInstance()
            ->setSubject('User delete request')
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->get('templating')
                    ->render($template,
                        array(
                            'mail'   =>  ['subject' => "Sol·licitud d'eliminació d'usuari ", 'body' => "L'usuari amb ID REC: '{$user->getId()}', DNI/NIE: '{$user->getUsername()}' i email '{$user->getEmail()}'
                             ha realitzat una sol·liciud perquè el seu usuari sigui eliminat."]
                        )
                    )
            )
            ->setContentType('text/html');

        $this->get('mailer')->send($message);
    }
}
