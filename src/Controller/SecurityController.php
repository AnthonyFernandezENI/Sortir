<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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
        if(!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();
            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();
            return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        } else {
            return $this->redirectToRoute('sortie_index');
        }


    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        if(!$this->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->redirectToRoute('app_login');
        } else {
            return $this->redirectToRoute('sortie_index');
        }
    }
}
