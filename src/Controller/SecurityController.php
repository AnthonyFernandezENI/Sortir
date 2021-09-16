<?php

namespace App\Controller;

use App\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use function PHPUnit\Framework\isEmpty;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }
        $repo = $this->getDoctrine()->getRepository(Participant::class);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            if(empty($repo->findAll())){
                return $this->redirectToRoute('profil_new');
            } else {
                // get the login error if there is one
                $error = $authenticationUtils->getLastAuthenticationError();
                // last username entered by the user
                $lastUsername = $authenticationUtils->getLastUsername();
            }
            return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        } else {
            return $this->redirectToRoute('sortie_index');
        }
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): Response
    {
        return $this->redirectToRoute('app_login');
    }
}
