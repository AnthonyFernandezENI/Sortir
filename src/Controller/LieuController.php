<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lieu")
 */
class LieuController extends AbstractController
{
    /**
     * @Route("/", name="lieu_index", methods={"GET"})
     */
    public function index(LieuRepository $lieuRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1){
                return $this->render('lieu/index.html.twig', [
                    'lieus' => $lieuRepository->findAll(),
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }

    }

    /**
     * @Route("/new", name="lieu_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                $lieu = new Lieu();
                $form = $this->createForm(LieuType::class, $lieu);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($lieu);
                    $entityManager->flush();

                    return $this->redirectToRoute('lieu_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('lieu/new.html.twig', [
                    'lieu' => $lieu,
                    'form' => $form,
                ]);
            }else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="lieu_show", methods={"GET"})
     */
    public function show(Lieu $lieu): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                return $this->render('lieu/show.html.twig', [
                    'lieu' => $lieu,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }

    }

    /**
     * @Route("/{id}/edit", name="lieu_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Lieu $lieu): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                $form = $this->createForm(LieuType::class, $lieu);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirectToRoute('lieu_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('lieu/edit.html.twig', [
                    'lieu' => $lieu,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="lieu_delete", methods={"POST"})
     */
    public function delete(Request $request, Lieu $lieu): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->getUser()->getAdministrateur() == 1) {
                if ($this->isCsrfTokenValid('delete' . $lieu->getId(), $request->request->get('_token'))) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($lieu);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('lieu_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }
}
