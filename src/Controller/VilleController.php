<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ville")
 */
class VilleController extends AbstractController
{
    /**
     * @Route("/", name="ville_index", methods={"GET"})
     */
    public function index(VilleRepository $villeRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                return $this->render('ville/index.html.twig', [
                    'villes' => $villeRepository->findAll(),
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/new", name="ville_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                $ville = new Ville();
                $form = $this->createForm(VilleType::class, $ville);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($ville);
                    $entityManager->flush();

                    return $this->redirectToRoute('ville_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('ville/new.html.twig', [
                    'ville' => $ville,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="ville_show", methods={"GET"})
     */
    public function show(Ville $ville): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                return $this->render('ville/show.html.twig', [
                    'ville' => $ville,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}/edit", name="ville_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Ville $ville): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                $form = $this->createForm(VilleType::class, $ville);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirectToRoute('ville_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('ville/edit.html.twig', [
                    'ville' => $ville,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="ville_delete", methods={"POST"})
     */
    public function delete(Request $request, Ville $ville): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                if ($this->isCsrfTokenValid('delete' . $ville->getId(), $request->request->get('_token'))) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($ville);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('ville_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }
}
