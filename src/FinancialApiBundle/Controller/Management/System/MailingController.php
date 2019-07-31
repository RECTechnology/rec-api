<?php

namespace App\FinancialApiBundle\Controller\Management\System;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LimitCount;
use App\FinancialApiBundle\Entity\LimitDefinition;
use App\FinancialApiBundle\Entity\Mail;
use App\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

/**
 * Class MailingController
 * @package App\FinancialApiBundle\Controller\System
 */
class MailingController extends BaseApiController
{
    function getRepositoryName()
    {
        return "FinancialApiBundle:Mail";
    }

    function getNewEntity()
    {
        return new Mail();
    }

    /**
     * @Rest\View
     * description: returns all mails
     * permissions: ROLE_SUPER_ADMIN ( all)
     */
    public function indexAction(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 100;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //only the superadmin can access here
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        $em = $this->getDoctrine()->getManager();
        $mails = $em->getRepository($this->getRepositoryName())->findAll();

        $total = count($mails);
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $mails
            )
        );

    }

    /**
     * @Rest\View
     * description: create a mail
     */
    public function createAction(Request $request){

        //only the superadmin can access here
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        return parent::createAction($request);
    }

    /**
     * @Rest\View
     */
    public function showAction($id){

        $em = $this->getDoctrine()->getManager();

        $mail = $em->getRepository($this->getRepositoryName())->find($id);
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => 1,
                'start' => 0,
                'end' => 1,
                'elements' => $mail
            )
        );
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN (all) , ROLE_RESELLER(sub-companies)
     */
    public function updateAction(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $mail = $em->getRepository('FinancialApiBundle:Mail')->find($id);

        if($mail->getStatus() == 'sent') throw new HttpException(403, 'This mail can\'t be modified because was sent');

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

    /**
     * @Rest\View
     */
    public function sendEmail(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $mail = $em->getRepository('FinancialApiBundle:Mail')->find($id);

        if(!$mail) throw new HttpException(404, 'Mail not found');

        $counter = 0;
        if($mail->getDst() == 'all'){
            $destinies = $em->getRepository('FinancialApiBundle:User')->findBy(array(
                'enabled'   =>  true
            ));
            foreach ($destinies as $destiny){
                $this->_sendEmail($mail, $destiny->getEmail());
                $counter++;
            }
            $mail->setStatus('sent');
            $mail->setCounter($counter);
            $mail->setUpdated(new \DateTime());
            $em->flush();

        }elseif ($mail->getDst() == 'kyc_managers'){
            throw new HttpException(403, 'Method not implemented yet');
        }else{
            $this->_sendEmail($mail, $mail->getDst());
            $counter++;
            $mail->setStatus('sent');
            $mail->setCounter($counter);
            $mail->setUpdated(new \DateTime());
            $em->flush();
        }

        return $this->restV2(204, 'success', 'Email send successfully');

    }

    private function _sendEmail($mail, $to){

        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        $template = 'FinancialApiBundle:Email:empty_email.html.twig';

        //TODO remove this part, only for test
        $mail_ex = explode('@',$to);
        $to = $mail_ex[0].'@robotunion.net';
        $message = \Swift_Message::newInstance()
            ->setSubject($mail->getSubject())
            ->setFrom($from)
            ->setTo(array(
                $to
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'mail'  =>  $mail
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);

    }

}
