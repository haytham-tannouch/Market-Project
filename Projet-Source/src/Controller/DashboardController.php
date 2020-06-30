<?php

namespace App\Controller;

use App\Entity\Agences;
use App\Entity\User;
use App\Form\AgenceCreationType;
use App\Form\ProfilType;
use App\Form\UserCreationType;
use App\Repository\AgencesRepository;
use App\Repository\PaysRepository;
use App\Repository\UserRepository;
use App\Repository\VillesRepository;
use Doctrine\Persistence\ObjectManager;
use Exception;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard/admin", name="dashboard_admin")
     * @param UserRepository $repository
     * @return Response
     */
    public function indexAdmin(UserRepository $repository)
    {
        $users=$repository->findAll();
        return $this->render('dashboard/index.html.twig', [
            'users' =>$users ,
            'admin'=>true
        ]);
    }
    /**
     * @Route("/dashboard/user", name="dashboard_user")
     * @param UserRepository $repository
     * @return Response
     */
    public function indexUser(UserRepository $repository)
    {
        $users=$repository->findAll();
        $this->getUser()->setLogedAt(new \DateTime());
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($this->getUser());
        $manager->flush();
        return $this->render('dashboard/index.html.twig', [
            'users' =>$users ,
            'admin'=>false
        ]);
    }

    /**
     * @Route("/agences", name="agences")
     * @param UserRepository $repository
     * @param AgencesRepository $agencesRepository
     * @param VillesRepository $villesRepository
     * @return Response
     */
    public function agenceListing(UserRepository $repository,AgencesRepository $agencesRepository , VillesRepository $villesRepository)
    {
        $users=$repository->findAll();
        $villes=$villesRepository->findAll();
        $agences=$agencesRepository->findAll();
        //dump($villes);die();
        return $this->render('dashboard/agences.html.twig', [
            'users' =>$users ,
            'agences'=>$agences,
            'villes'=>$villes
        ]);
    }

    /**
     * @Route("/agences/Profil/{id}", name="profil")
     * @param $request
     * @param $repository
     * @return Response
     */
    public function Profil(Request $request,UserRepository $repository,AgencesRepository $agencesRepository)
    {
        $form = $this->createForm(ProfilType::class);
        $form->handleRequest($request);
        $data=$request->attributes->all();
        $user=$repository->find($data['id']);
        $agence=$agencesRepository->findOneBy(['Utilisateur' => $data['id']]);

        return $this->render('dashboard/Profil.html.twig', [
            'ProfilForm' => $form->createView(),
            'user'=>$user,
            'agence'=>$agence

        ]);

    }

    /**
     * @Route("/dashboard/user/{id}",name="user_profil")
     */
    public function userProfil(Request $request, UserPasswordEncoderInterface $encoder,User $user,\Swift_Mailer $mailer)
    {
        $users = $this->getDoctrine()->getRepository(User::class);
        $data=$request->attributes->all();
        $Olduser = $users->findOneBy(['id' => $data['id']]);
        $oldPass=$Olduser->getPassword();

        $modif=true;
        if ($this->getUser()==$user){
            $modif=false;
        }
        $form = $this->createForm(UserCreationType::class, $user,['disabled'=>$modif]);
        $form->handleRequest($request);
        $data=$form->getData();
        $params = $request->request->all();
        if ($form->isSubmitted() && $form->isValid()) {
            dump("oldpass".$oldPass);
            $newpass=$params['user_creation']['password'];
            if ($newpass!=$oldPass){
                $hash = $encoder->encodePassword($user,$newpass);
                $user->setPassword($hash);
            }
            else{
                $user->setPassword($oldPass);
            }
            $role=$data->getRole();
            if($role=="Administrateur"){
                $user->addRole('ROLE_ADMIN');

            }
            elseif ($role=="Editeur"){
                $user->addRole('ROLE_USER');
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            //return $this->redirectToRoute('dashboard');
            }
            return $this->render('dashboard/modifier.html.twig', [
                'form' => $form->createView(),
                'user'=>$user,
                'oldpassword'=>$oldPass,
            ]);



    }

    /**
     * @Route("/dashboard/admin/createUser", name="createUser")
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
     * @Route("/dashboard/admin/createAgence", name="createAgence")
     */
    public function createAgence(Request $request,PaysRepository $paysRepository,VillesRepository $villesRepository,AgencesRepository $agencesRepository,UserRepository $userRepository)
    {
        //dump($agencesRepository->findByExampleField());die();

        $agence = new Agences();
        $form = $this->createForm(AgenceCreationType::class, $agence);
        $form->handleRequest($request);

        $data=$form->getData();
        if ($form->isSubmitted() && $form->isValid()) {
            $params = $request->request->all();
            $ville=$params['Ville'];
            $pays=$params['Pays'];
            //dump($params);die();
            $p=$paysRepository->findOneByName($pays);
            $v=$villesRepository->findOneByName($ville);
            //dump($v);die();
            $agence->setPays($p);
            $agence->setVille($v);
            $agence->setEtat(false);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($agence);
            $manager->flush();
            }

        return $this->render('dashboard/creationAgence.html.twig', [
            'form' => $form->createView(),
            'pays'=>$paysRepository->findAll(),
            'villes'=>$villesRepository->findAll(),
            'agences'=>$agencesRepository->findAll(),
            'users'=>$userRepository->findAll(),
        ]);
    }


    /**
     * @Route("/dashboard/admin/edit/{id}", name="editUser")
     */
    public function editUser(Request $request, UserPasswordEncoderInterface $encoder,User $user,\Swift_Mailer $mailer)
    {
        $users = $this->getDoctrine()->getRepository(User::class);
        $data=$request->attributes->all();
        $Olduser = $users->findOneBy(['id' => $data['id']]);
        $oldPass=$Olduser->getPassword();

        $form = $this->createForm(UserCreationType::class, $user,['required'=>false]);
        $form->handleRequest($request);
        $data=$form->getData();
        $params = $request->request->all();
        if ($form->isSubmitted() && $form->isValid()) {
           // dump("oldpass".$oldPass);
            $newpass=$params['user_creation']['password'];
            if ($newpass!=$oldPass){
                $hash = $encoder->encodePassword($user,$newpass);
                $user->setPassword($hash);
            }
            else{
                $user->setPassword($oldPass);
            }
            $role=$data->getRole();
            if($role=="Administrateur"){
                $user->addRole('ROLE_ADMIN');

            }
            elseif ($role=="Editeur"){
                $user->addRole('ROLE_USER');
            }
            /*if($message=="on"){
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
            }*/

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            //return $this->redirectToRoute('dashboard');
        }
        return $this->render('dashboard/modifier.html.twig', [
            'form' => $form->createView(),
            'user'=>$user,
            'oldpassword'=>$oldPass,
            'firstlog'=>false
        ]);

    }
    /**
     * @Route("/dashboard/admin/user/{id}/isActive", name="isActive")
     * @param UserRepository $repository
     * @return Response
     */
    public function isActive(User $user,Request $request,UserRepository $repository,AgencesRepository $agencesRepository)
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
     * @Route("dashboard/admin/agences/{id}/isAGactive", name="isAGactive")
     * @param AgencesRepository $repository
     * @return Response
     */
    public function isAGative(Agences $agence,Request $request,AgencesRepository $agencesRepository)
    {
        $data=$request->attributes->all();
        // dump($data);die();
        $agence=$data['agence'];
        if($agence->getEtat()==true){
            $agence->setEtat(false);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($agence);
            $manager->flush();
            return $this->json(['code'=>200,'etat'=>'false','agenceid'=>$agence->getId()],200);
        }
        elseif ($agence->getEtat()==false){
            $agence->setEtat(true);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($agence);
            $manager->flush();
            return $this->json(['code'=>200,'etat'=>'true','agenceid'=>$agence->getId()],200);
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

    /**
     * @Route("/paysDiv/{pays}",name="paysDiv")
     * @param Request $request
     * @param VillesRepository $villesRepository
     * @param PaysRepository $paysRepository
     */
    public function paysDiv(Request $request,VillesRepository $villesRepository,PaysRepository $paysRepository)
    {
        $country=array();
        $pays=$paysRepository->findByName($request->attributes->get('pays'));
        $villes=$villesRepository->findByPays($pays);
        foreach ($villes as $ville){
            array_push($country,$ville->getNomVille());
    }
        return $this->json(['code'=>200,'villes'=>$country],200);}

}
