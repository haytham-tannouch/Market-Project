<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgottenpassType;
use App\Form\ResetPassType;
use App\Form\ValidationCodeType;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(Request $request,AuthenticationUtils $authenticationUtils): Response
    {
        //dump($this->getUser());
        if ($this->getUser()) {
            $token = new UsernamePasswordToken($this->getUser(), null, 'main', $this->getUser()->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirect($this->generateUrl('dashboard'));
            }
            else{
                return $this->redirect($this->generateUrl('dashboard'));
            }
        }
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
           return $this->redirectToRoute('dashboard_admin');
        } if ($this->isGranted('ROLE_USER')) {
        return $this->redirectToRoute('dashboard_user');
    }


    }
    /**
     * @Route("/Remail", name="app_remail")
     */
    public function Remail(Request $request,ObjectManager $manager,UserRepository $users,\Swift_Mailer $mailer): Response
    {
        if ($this->getUser()) {
            $token = new UsernamePasswordToken($this->getUser(), null, 'main', $this->getUser()->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirect($this->generateUrl('dashboard'));
            }
            else{
                return $this->redirect($this->generateUrl('app_login'));
            }
        }
        //initialisatoin Emailform du form qui contient une seule input de type email
        $form = $this->createForm(ForgottenpassType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            //recuperation de lemail envoyé
            $donnees = $form->getData();
            //la recherche de l email
            $user = $users->findOneByEmail($donnees->getEmail());
            // si aucun user a cet email on l e renvoit vers la mm page
            if ($user === null) {
                echo "<script>alert(\"l'adresse de courriel saisie n'existe pas sur notre base de données,veuillez la verifier puis réessayer \")
                   </script>";
                return $this->render('security/Email.html.twig', [
                    'emailForm' => $form->createView()
                ]);
            }
            // sinon on lui genere un f=token a six chiffre
            //$forgotten_token=random_int(0,9).random_int(0,9).random_int(0,9).random_int(0,9).random_int(0,9).random_int(0,9);
            $forgotten_token="123456";
            $encoded =md5($forgotten_token);
            $user->setForgottenPassToken($encoded);

            $date=new \DateTime();
            $user->setForgottenPassExpiration($date);
            $date->modify('+120 seconds');
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            // envoi de l email  de confirmation
            $message = (new \Swift_Message('Mot de passe oublié'))
                ->setFrom('votre@adresse.fr')
                ->setTo($user->getEmail())
                ->setBody(
                    "Vous allez recevoir un code pour reinitialiser vore mot de passe a dresse mail :  " . $user->getEmail() ,
                    'text/html'
                )
            ;
            //envoi de l email de recuperation de mdp
            $message2 = (new \Swift_Message('recuperation mdp'))
                ->setFrom('votre@adresse.fr')
                ->setTo($user->getEmail())
                ->setBody(
                    "bonjour".$user->getNom()." ".$user->getPrenom()." Vs avez demandé la reinitialisation du mdp  de votre compte sur  WWW.SITE.MA Voici le code de securité pr changer votre mdp : ".$forgotten_token." Cordialement ",
                    'text/html'
                )
                ;
            $mailer->send($message);$mailer->send($message2);
            // si les emails ont ete envoyé je le redirige vers la page validationcode.html.twig pr saisir le token qu'il vient de recevoir passant sont id pr verification

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
            $code=md5($data['code']);
            $confirmationCode =$user->getForgottenPassToken();
            $CurrentDate=new \DateTime();
        //    $Period=$CurrentDate->diff($user->getForgottenPassExpiration(),false);

           // dump($Period->i);
            //dump($Period->s);die();

            $diff = $user->getForgottenPassExpiration()->getTimestamp() - $CurrentDate->getTimestamp();
            //dump($diff);die();
          if ($diff>0 && $diff<120)
          {
              if($code==$confirmationCode){

                  return $this->redirectToRoute('app_reset_password',[
                      'code'=>$confirmationCode
                  ]);


              }
              else
              {
                  echo "<script>alert(\"Code de validation incorrect  Réssayez à nouveau \")
                   </script>";
              }
            }
            else{
                echo "<script>alert(\"Durée de ce code de validation a été expirée  Réssayez à nouveau \")
                   </script>";
            }


        }
        return $this->render('security/ValidationCode.html.twig', [
            'ConfirmForm' => $form->createView()
        ]);

    }


    /**
     * @Route("Remail/reset_pass/{code}", name="app_reset_password")
     */
    public function resetPassword(Request $request, UserRepository $repository, UserPasswordEncoderInterface $passwordEncoder,\Swift_Mailer $mailer)
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

            return $this->redirectToRoute('app_login');
        }

        // Si le formulaire est envoyé en méthode post
        if ($request->isMethod('POST')) {
            // On supprime le token
            $user->setForgottenPassToken(null);
            $user->setForgottenPassExpiration(null);
            $params=$request->request->all();
            $pass=$params['reset_pass'];
            // dump($pass['password']);die();
            $user->setPassword($passwordEncoder->encodePassword($user, $pass['password']));

            // On stocke
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            // On le connecte automatiquement apres la saisie du mdp

            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
            return $this->redirect($this->generateUrl('dashboard'));

            $this->addFlash('message', 'validation changement de mdp');
            $message = (new \Swift_Message('validation changement de mdp'))
                ->setFrom('votre@adresse.fr')
                ->setTo($user->getEmail())
                ->setBody(
                    "bonjour".$user->getNom()." ".$user->getPrenom()." Vs avez reinitaliseé vootre mdp avc succes  Vs pouvez maintenant vs connecter et profiter des avantages  de votre espace personnel cordialement , l equipe WWW.SITE.MA",
                    'text/html'
                )
            ;
            $mailer->send($message)
                ;

            // On redirige vers la page de connexion
            //return $this->redirectToRoute('app_login');
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