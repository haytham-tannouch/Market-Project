<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserCreationType;
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
     * @return Response
     * @throws Exception
     */
    public function createUser(Request $request,UserPasswordEncoderInterface $encoder)
    {
        $user = new User();
        $form = $this->createForm(UserCreationType::class, $user);

        $form->handleRequest($request);
        $data=$form->getData();
        if ($form->isSubmitted()) {

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
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('dashboard');

        }

        return $this->render('dashboard/creationUser.html.twig', [
            'form' => $form->createView()
        ]);

    }
}
