<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Utilisateur;
use App\Form\EmailType;
use App\Form\ForgottenpassType;
use App\Form\ResetPassType;
use App\Form\ValidationCodeType;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/afterlogin", name="app_afterlogin")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function afterlogin(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->isGranted('ROLE_ADMIN')) {
           return $this->redirectToRoute('dashboard');
        }

    }
    /**
     * @Route("/Remail", name="app_remail")
     */
    public function Remail(Request $request,ObjectManager $manager,UserRepository $users,\Swift_Mailer $mailer): Response
    {
        $form = $this->createForm(ForgottenpassType::class);
        $form->handleRequest($request);
        //dump($form);die();
        if ($form->isSubmitted() && $form->isValid()) {
            //dump($form);die();
            $donnees = $form->getData();
            $user = $users->findOneByEmail($donnees->getEmail());
            if ($user === null) {
                return $this->render('security/Email.html.twig');
            }
            $forgotten_token=random_int(0,9).random_int(0,9).random_int(0,9).random_int(0,9).random_int(0,9).random_int(0,9);
            $user->setForgottenPassToken($forgotten_token);
            $date=new \DateTime();
            $user->setForgottenPassExpiration($date);
            $date->modify('+120 seconds');


            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();

            // envoi de l email
            $message = (new \Swift_Message('Mot de passe oublié'))
                ->setFrom('votre@adresse.fr')
                ->setTo($user->getEmail())
                ->setBody(
                    "Vous allez recevoir un code pour reinitialiser vore mot de passe a dresse mail : " . $user->getEmail() ,
                    'text/html'
                )
            ;
            $message2 = (new \Swift_Message('Code de confirmation'))
                ->setFrom('votre@adresse.fr')
                ->setTo($user->getEmail())
                ->setBody(
                    "Code de confirmation Suivant:".$user->getForgottenPassToken(),
                    'text/html'
                )
                ;
            $mailer->send($message);$mailer->send($message2);
           if ( $mailer->send($message) && $mailer->send($message2)){

               return $this->redirectToRoute('validationCode',[
                   'id'=>$user->getId()
               ]);
           }

        }
        return $this->render('security/Email.html.twig', [
            'emailForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/Remail/validation/{id}",name="validationCode")
     * @param Request $request
     */
    public function validationCode(Request $request,UserRepository $repository)
    {
        $form = $this->createForm(ValidationCodeType::class);
        $form->handleRequest($request);
        $data=$request->attributes->all();
        $user=$repository->find($data['id']);
        if ($form->isSubmitted() && $form->isValid()) {

            $params = $request->request->all();
            $data=$params['validation_code'];
            $code=$data['code'];
            $confirmationCode =$user->getForgottenPassToken();
            if ($user->getForgottenPassExpiration()<new \DateTime())
            {
                if($code==$confirmationCode){

                    return $this->redirectToRoute('app_reset_password',[
                        'code'=>$confirmationCode
                    ]);
                }
            }


        }
        return $this->render('security/ValidationCode.html.twig', [
            'ConfirmForm' => $form->createView()
        ]);
    }


    /**
     * @Route("Remail/reset_pass/{code}", name="app_reset_password")
     */
    public function resetPassword(Request $request, UserRepository $repository, UserPasswordEncoderInterface $passwordEncoder)
    {
        // On cherche un utilisateur avec le token donné
        $form = $this->createForm(ResetPassType::class);
        $form->handleRequest($request);
        //dump($form);die();
        $data=$request->attributes->all();
        $user=$repository->findOneBy(array('forgottenPass_token' => $data['code']));
        //dump($user);die();
        // Si l'utilisateur n'existe pas
        if ($user === null) {
            // On affiche une erreur
            $this->addFlash('danger', 'Code Inconnu');
            return $this->redirectToRoute('login');
        }

        // Si le formulaire est envoyé en méthode post
        if ($request->isMethod('POST')) {
            // On supprime le token
            $user->setForgottenPassToken(null);
            $user->setForgottenPassExpiration(null);
            dump($user);die();
            // On chiffre le mot de passe
            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));

            // On stocke
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // On crée le message flash
            $this->addFlash('message', 'Mot de passe mis à jour');

            // On redirige vers la page de connexion
            return $this->redirectToRoute('app_login');
        }else {
            // Si on n'a pas reçu les données, on affiche le formulaire
            //return $this->render('security/resetPass.html.twig');
        }

        return $this->render('security/resetPass.html.twig', [
            'ResetForm' => $form->createView()
        ]);

    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
       // return $this->redirectToRoute('app_login');
    }
}