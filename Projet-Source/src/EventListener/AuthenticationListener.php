<?php


namespace App\EventListener;

use App\Entity\Login;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;


class AuthenticationListener
{

    private $doctrine;

    private $request;


    /**
     * @param $doctrine
     * @param $request
     */
    public function __construct($doctrine, $request)
    {
        $this->doctrine = $doctrine;
        $this->request = $request;
    }

    /**
     * onAuthenticationFailure
     *
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $username = $event->getAuthenticationToken()->getUsername();
        $this->saveLogin($username, false);

    }

    /**
     * onAuthenticationSuccess
     *
     * @param InteractiveLoginEvent $event
     */
    public function onAuthenticationSuccess(InteractiveLoginEvent $event)
    {
        $username = $event->getAuthenticationToken()->getUsername();
        $this->saveLogin($username, true);
    }

    /**
     * onAuthenticationSuccess
     *
     * @param $success
     * @param $username
     */
    private function saveLogin($username, $success) {
        $login = new Login();
       // $login->setUsername($username);
        $login->setIp($this->request->getCurrentRequest()->getClientIp());
        $login->setSuccess($success);
        $timestamp = new \DateTime('now');
        $login->getTimestamp($timestamp);

        $em = $this->doctrine->getManager();
        $em->persist($login);
        $em->flush();
    }
}