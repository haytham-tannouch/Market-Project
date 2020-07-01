<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserCreationType;
use App\Repository\SettingsRepository;
use App\Security\UserAuthenticator;
use http\Params;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class InscriptionController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     */
    public function inscription(Request $request,SettingsRepository $settingsRepository, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, UserAuthenticator $authenticator,\Swift_Mailer $mailer):Response
    {
        if ($settingsRepository->find('1')->getInscription()==false){
            return $this->render('/Settings/maintenance.html.twig');
        }
        $user = new User();
        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class, array(
                'attr' => array(
                    'class' => 'form-control'
                )))
            ->add('password', PasswordType::class, array(
                'attr' => array(
                    'class' => 'form-control'
                )))
            ->add('Sauvegarder', SubmitType::class, array(
                'attr' => array(
                    'class' => 'btn btn-primary'
                )))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $user->setInscription(new \DateTime());
            $user->setRole("Editeur");
            $user->addRole('ROLE_USER');
            $user->setEtat(false);
            // On génère un token et on l'enregistre
            $user->setActivationToken(md5(uniqid()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

        }

        // On crée le message
        $message = (new \Swift_Message('Nouveau compte'))
            // On attribue l'expéditeur
            ->setFrom('votre@adresse.fr')
            // On attribue le destinataire
            ->setTo($user->getEmail())
            // On crée le texte avec la vue
            ->setBody(
                $this->renderView(
                    'emails/activation.html.twig', ['token' => $user->getActivationToken()]
                ),
                'text/html'
            )
        ;
        $notif = (new \Swift_Message('Nouveau compte Créé'))
            // On attribue l'expéditeur
            ->setFrom('votre@adresse.fr')
            // On attribue le destinataire
            ->setTo('admin@admin.com')
            // On crée le texte avec la vue
            ->setBody(
               "Un nouveau Compte est créé par : ".$user->getEmail()." !",
                'text/html'
            )
        ;
        $mailer->send($notif);
        $mailer->send($message);


        return $this->render('/inscription/register.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }


}
