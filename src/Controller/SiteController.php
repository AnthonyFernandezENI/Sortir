<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/site")
 */
class SiteController extends AbstractController
{
    /**
     * @Route("/", name="site_index", methods={"GET"})
     */
    public function index(SiteRepository $siteRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                return $this->render('site/index.html.twig', [
                    'sites' => $siteRepository->findAll(),
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/new", name="site_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                $site = new Site();
                $form = $this->createForm(SiteType::class, $site);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($site);
                    $entityManager->flush();

                    return $this->redirectToRoute('site_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('site/new.html.twig', [
                    'site' => $site,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="site_show", methods={"GET"})
     */
    public function show(Site $site): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                return $this->render('site/show.html.twig', [
                    'site' => $site,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}/edit", name="site_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Site $site): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                $form = $this->createForm(SiteType::class, $site);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirectToRoute('site_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('site/edit.html.twig', [
                    'site' => $site,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="site_delete", methods={"POST"})
     */
    public function delete(Request $request, Site $site): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if($this->getUser()->getAdministrateur() == 1) {
                if ($this->isCsrfTokenValid('delete' . $site->getId(), $request->request->get('_token'))) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($site);
                    $entityManager->flush();
                }

                return $this->redirectToRoute('site_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }
}
