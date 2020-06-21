<?php

namespace App\Controller;

use App\Form\LoginType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurutyController extends Controller
{

    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @param $donnees
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function login(Request $request,AuthenticationUtils $utils)
    {
        $form = $this->createForm(LoginType::class);

        $error=$utils->getLastAuthenticationError();

        $lastusername=$utils->getLastUsername();

        return $this->render('securuty/Login.html.twig', [
            'form' => $form->createView(),
            'error'=>$error,
            'lastusername'=>$lastusername
        ]);
    }

}
