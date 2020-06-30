<?php

namespace App\Controller;

use App\Entity\Agences;
use App\Entity\Email;
use App\Entity\User;
use App\Form\AgenceCreationType;
use App\Form\GestionEmailsType;
use App\Form\UserCreationType;
use App\Repository\AgencesRepository;
use App\Repository\EmailRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Exception;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard", name="dashboard")
     * @param UserRepository $repository
     * @return Response
     */
    public function index(UserRepository $repository)
    {
        $users=$repository->findAll();
        return $this->render('dashboard/index.html.twig', [
            'users' =>$users ,
        ]);
    }

    /**
     * @Route("/dashboard/createUser", name="createUser")
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @param \Swift_Mailer $mailer
     * @return Response
     * @throws Exception
     */
    public function createUser(Request $request,UserPasswordEncoderInterface $encoder, \Swift_Mailer $mailer)
    {
        $user = new User();
        $form = $this->createForm(UserCreationType::class, $user);
        $form->handleRequest($request);
        $data=$form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $params = $request->request->all();
            $message=$params['message'];
            //dump($request);die();
            //dump($params['user_creation']['password']);die();
            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash);
            $user->setEtat(true);
            $role=$data->getRole();
            if($role=="Administrateur"){
                $user->addRole('ROLE_ADMIN');
            }
            elseif ($role=="Editeur"){
                $user->addRole('ROLE_USER');
            }

            $user->setInscription(new \DateTime());
            if($message=="on"){
                $msg = (new \Swift_Message('Creation Compte'))
                    ->setFrom('votre@adresse.fr')
                    ->setTo($user->getEmail())
                    ->setBody(
                        "Bonjour,<br><br>Bonjour Un compte est créé pour vous, merci de se connecter en cliquant sur le lien suivant : <a href='127.0.0.1:8000'>Se Connecter</a><br>
                                    Vos Cordonnées D'authentification: <br><label for='email'></label><b id='email'>".$user->getEmail()."</b><br><label for='password'><b>".$params['user_creation']['password']."</b></label>",
                        'text/html'
                    )
                ;
                // On envoie l'e-mail
                $mailer->send($msg);
            }


            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            //return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/creationUser.html.twig', [
            'form' => $form->createView()
        ]);

    }
    /**
     * @Route("/dashboard/createAgence", name="createAgence")
     */
    public function createAgence(Request $request)
    {
        $agence = new Agences();
        $form = $this->createForm(AgenceCreationType::class, $agence);
        $form->handleRequest($request);
        $data=$form->getData();



        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($agence);
            $manager->flush();

            }

        return $this->render('dashboard/creationAgence.html.twig', [
            'form' => $form->createView(),
        ]);

    }
    /**
     * @Route("/dashboard/admin/gestionemail", name="gestionemail")
     * @param EmailRepository $repository
     * @return Response
     */
    public function listeemail(EmailRepository $repository, UserRepository $repo)
    {
        $emails = $repository->findAll();
        $users=$repo->findAll();
        return $this->render('dashboard/indexEmail.html.twig', [
            'emails' => $emails,
            'users'=>$users
        ]);
    }
    /**
     * @Route("/dashboard/admin/createEmail", name="createEmail")
     */
    public function createEmail(Request $request)
    {
        $email = new Email();
        $form = $this->createForm(GestionEmailsType::class, $email);
        $form->handleRequest($request);
        $data=$form->getData();



        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($email);
            $manager->flush();

        }

        return $this->render('dashboard/Email.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/dashboard/admin/gestionemail/getuser/{type}",name="gestionemailuser")
     * @param Request $request
     * @param EmailRepository $emailRepository
     * @param UserRepository $userRepository
     */
    public function EmailUser(Request $request, EmailRepository $emailRepository,  UserRepository $userRepository)
    {
        //$Nom = array();
       // $Email=array();
       // $Poste=array();
        $respense = array();
        $users = $userRepository->findAll();
        $emails = $emailRepository->findAll();

        foreach ($emails as $email) {
            foreach ($users as $user){
                if($user->getRole()==$request->attributes->get('type') && $email->getUser()==$request->attributes->get('type')){

                  array_push($respense,$user);

                }elseif ($user->getRole()==$request->attributes->get('type') && $email->getUser()==$request->attributes->get('type')){

                    array_push($respense,$user);


                }
            }

        }
        return $this->json(['code' => 200, 'table' => $respense], 200);
    }

    /**
     * @Route("/dashboard/user/{id}/isActive", name="isActive")
     * @param UserRepository $repository
     * @return Response
     */
    public function isActive(User $user,Request $request,UserRepository $repository)
    {
        $data=$request->attributes->all();
        $user=$data['user'];

        if($user->getEtat()==true){
            $user->setEtat(false);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            return $this->json(['code'=>200,'etat'=>'false','userid'=>$user->getId()],200);
        }
        elseif ($user->getEtat()==false){
            $user->setEtat(true);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            return $this->json(['code'=>200,'etat'=>'true','userid'=>$user->getId()],200);
        }
        else{ return $this->json(['code'=>403,'message'=>'erro'],200);}

    }
    /**
     * @Route("/genegerPass", name="genPass")
     */
    public function generatePassword()
    {
            $characters = 'abcdefghijklmnopqrstuvwxyz';
            $num = '0123456789';
            $charmaj='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $numLength = strlen($num);
            $charmajLength = strlen($charmaj);
            $randomString = '';
            for ($i = 0; $i < 3; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)].$num[rand(0, $numLength - 1)].$charmaj[rand(0, $charmajLength - 1)];
            }
            //dump($randomString);die();
            return $this->json(['code'=>200,'RandPass'=>$randomString],200);
    }
}
