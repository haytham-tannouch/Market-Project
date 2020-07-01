<?php

namespace App\Controller;

use App\Entity\Agences;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\AgenceCreationType;
use App\Form\ProfilType;
use App\Form\SettingsType;
use App\Form\UserCreationType;
use App\Repository\AgencesRepository;
use App\Repository\PaysRepository;
use App\Repository\SettingsRepository;
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
        public function indexUser(UserRepository $repository,SettingsRepository $settingsRepository)
        {
            $setting=$settingsRepository->find('1');
            if ($setting->getModeMaintenance()==true)
            {
                if( $this->isGranted('ROLE_USER'))
                {


                    return $this->render('Settings/maintenance.html.twig');
                }
            }


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
        public function agenceListing(UserRepository $repository,AgencesRepository $agencesRepository , VillesRepository $villesRepository,SettingsRepository $settingsRepository)
        {
            $setting=$settingsRepository->find('1');
            if ($setting->getModeMaintenance()==true)
            {

                return $this->render('Settings/maintenance.html.twig');

            }


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
        public function Profil(Request $request,UserRepository $repository,AgencesRepository $agencesRepository,SettingsRepository $settingsRepository)
        {
            $setting=$settingsRepository->find('1');
            if ($setting->getModeMaintenance()==true)
            {

                    return $this->render('Settings/maintenance.html.twig');

            }


            $form = $this->createForm(ProfilType::class);
            $form->handleRequest($request);
            $data=$request->attributes->all();
            $user=$repository->find($data['id']);
            $agence=$agencesRepository->findOneBy(['Utilisateur' => $data['id']]);

            $agences=$agencesRepository->findAll();
            return $this->render('dashboard/Profil.html.twig', [
                'ProfilForm' => $form->createView(),
                'user'=>$user,
                'agences'=>$agences,
                'agence'=>$agence


            ]);

        }

        /**
         * @Route("/dashboard/user/{id}",name="user_profil")
         */
        public function userProfil(Request $request, UserPasswordEncoderInterface $encoder,User $user,\Swift_Mailer $mailer,SettingsRepository $settingsRepository)
        {
            $setting=$settingsRepository->find('1');
            if ($setting->getModeMaintenance()==true)
            {
                if( $this->isGranted('ROLE_ADMIN'))
                {
                    return $this->redirectToRoute('settings');
                }
                else
                {
                    return $this->render('Settings/maintenance.html.twig');
                }
            }


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

        $agence = new Agences();
        $form = $this->createForm(AgenceCreationType::class, $agence);
        $form->handleRequest($request);

            $data=$form->getData();
            if ($form->isSubmitted() && $form->isValid()) {
                $params = $request->request->all();
                $ville=$params['Ville'];
                $pays=$params['Pays'];
                $p=$paysRepository->findOneByName($pays);
                $v=$villesRepository->findOneByName($ville);
                $u=$params['userAg'];
                $Utilisateur=$userRepository->find($u);
                //dump($userRepository->find($u));die();

                //dump($v);die();
                $agence->setUtilisateur($Utilisateur);
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
            'users'=>$userRepository->findByExampleField()

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
         * @Route("/dashboard/admin/settings", name="settings")
         */
        public function settings(Request $request,SettingsRepository $settingsRepository)
        {
            //dump('test');die();
            $settings = $this->getDoctrine()->getRepository(Settings::class);
            $data=$request->attributes->all();
            $setting = $settings->find('1');
            $form = $this->createForm(SettingsType::class, $setting,['required'=>false]);
            $form->handleRequest($request);
            $data=$form->getData();
            $params = $request->request->all();
            if ($form->isSubmitted() && $form->isValid()){
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($setting);
                $manager->flush();
                //dump($setting);die();
            }

            return $this->render('dashboard/settings.html.twig',[
               'setting'=>$setting,
                'form'=>$form->createView()
            ]);

        }

        /**
         * @Route("dashboard/admin/settings/maintenance", name="maintenance")
         * @param Request $request
         * @param SettingsRepository $settingsRepository
         * @param $event
         * @return \Symfony\Component\HttpFoundation\JsonResponse
         */
        public function maintenance(Request $request, SettingsRepository $settingsRepository)
        {

            $setting=$settingsRepository->find('1');
           // dump($setting);die();
            if($setting->getModeMaintenance()==true){
                $setting->setModeMaintenance(false);
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($setting);
                $manager->flush();
                return $this->json(['code'=>200,'ModeMaintenance'=>'false'],200);
            }
            elseif ($setting->getModeMaintenance()==false){
                $setting->setModeMaintenance(true);

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($setting);
                $manager->flush();
               return $this->json(['code'=>200,'ModeMaintenance'=>'true'],200);

            }
            return $this->json(['code'=>403,'message'=>'erro'],200);

        }
    /**
     * @Route("dashboard/admin/settings/isInscriptionActive", name="isInscriptionActive")
     * @param Request $request
     * @param SettingsRepository $settingsRepository
     * @param $event
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function isInscriptionActive(Request $request, SettingsRepository $settingsRepository)
    {

        $setting=$settingsRepository->find('1');
        // dump($setting);die();
        if($setting->getInscription()==true){
            $setting->setInscription(false);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($setting);
            $manager->flush();
            return $this->json(['code'=>200,'inscription'=>'false'],200);
        }
        elseif ($setting->getInscription()==false){
            $setting->setInscription(true);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($setting);
            $manager->flush();
            return $this->json(['code'=>200,'inscription'=>'true'],200);

        }
        return $this->json(['code'=>403,'message'=>'erro'],200);

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
            foreach ($villes as $ville)
            {
                array_push($country,$ville->getNomVille());
            }
            return $this->json(['code'=>200,'villes'=>$country],200);
        }

    /**
     * @Route("/forcer", name="forcer")

     * @param SettingsRepository $settingsRepository
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function forcer( SettingsRepository $settingsRepository)
    {
        $setting = $settingsRepository->find('1');
        if ($setting->getModeMaintenance() == true)
            {
                //$template = $this->render('Settings/maintenance.html.twig');

                // We send our response with a 503 response code (service unavailable)
                //$event->setResponse(new Response('site down',Response::HTTP_SERVICE_UNAVAILABLE));
                //  $event->stopPropagation();
                return $this->render('Settings/maintenance.html.twig');
            }
    }
    /**
     * @Route("/dashboard/admin/user/{id}/supp", name="suppUser")
     * @param UserRepository $repository
     * @return Response
     */
    public function suppUser(User $user,Request $request,UserRepository $repository,AgencesRepository $agencesRepository)
    {

        $data=$request->attributes->all();
        $user=$data['user'];

        $agence=$agencesRepository->findOneByUser($user);

        //dump($agence);die();
            $manager = $this->getDoctrine()->getManager();
            $manager->remove($user);
            $manager->remove($agence);
            $manager->flush();

         return $this->json(['code'=>403,'message'=>'success'],200);

    }
    /**
     * @Route("/dashboard/admin/user/{id}/archive", name="archiveUser")
     * @param UserRepository $repository
     * @return Response
     */
    public function archiveUser(User $user,Request $request,UserRepository $repository,AgencesRepository $agencesRepository)
    {
            $data=$request->attributes->all();
            $user=$data['user'];
            //dump($user);die();
            $user->setStatus(false);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();

         return $this->json(['code'=>403,'message'=>'success'],200);
    }

  }
