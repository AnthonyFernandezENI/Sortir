<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use function PHPUnit\Framework\isEmpty;

/**
 * @Route("/profil")
 */
class ProfilController extends AbstractController
{
    /**
     * @Route("/", name="profil_index", methods={"GET"})
     */
    public function index(ParticipantRepository $participantRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->getUser()->getAdministrateur() == 1) {
                return $this->render('profil/index.html.twig', [
                    'participants' => $participantRepository->findAll(),
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/new", name="profil_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $repo = $this->getDoctrine()->getRepository(Participant::class);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            if (empty($repo->findAll())) {
                $participant = new Participant();
                $form = $this->createForm(ParticipantType::class, $participant);
                $form->handleRequest($request);
//                dd($form);
                if ($form->isSubmitted() && $form->isValid()) {
                    $participant->setAdministrateur(true);
                    $participant->setActif(true);
                    $participant->setPassword(

                        $passwordEncoder->encodePassword(
                            $participant,
                            $form->get('password')->getData()
                        )
                    );
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($participant);
                    $entityManager->flush();
                    return $this->redirectToRoute('app_login');
                }
                return $this->renderForm('profil/new.html.twig', [
                    'participant' => $participant,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('app_login');
            }
        } else {
            if ($this->getUser()->getAdministrateur() == 1) {
                $participant = new Participant();
                $form = $this->createForm(ParticipantType::class, $participant);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {

                    $participant->setAdministrateur(false);
                    $participant->setActif(true);
                    $participant->setPassword(

                        $passwordEncoder->encodePassword(
                            $participant,
                            $form->get('password')->getData()
                        )
                    );
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($participant);
                    $entityManager->flush();


                    return $this->redirectToRoute('profil_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('profil/new.html.twig', [
                    'participant' => $participant,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('sortie_index');
            }
        }
    }

    /**
     * @Route("/{id}", name="profil_show", methods={"GET"})
     */
    public function show(Participant $participant): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            return $this->render('profil/show.html.twig', [
                'participant' => $participant,
            ]);
        }
    }

    /**
     * @Route("/{id}/edit", name="profil_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, UserPasswordEncoderInterface $passwordEncoder, Participant $participant): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if (($this->getUser()->getAdministrateur() == 1) || ($this->getUser()->getId() == $participant->getId())) {
                $form = $this->createForm(ParticipantType::class, $participant);
                $form->handleRequest($request);
//        dd($participant);
                if ($form->isSubmitted() && $form->isValid()) {

                    $participant->setPassword(

                        $passwordEncoder->encodePassword(
                            $participant,
                            $form->get('password')->getData()
                        )
                    );
//            $this->getDoctrine()->getManager()->flush();
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($participant);
                    $entityManager->flush();
                    return $this->redirectToRoute('profil_index', [], Response::HTTP_SEE_OTHER);
                }

                return $this->renderForm('profil/edit.html.twig', [
                    'participant' => $participant,
                    'form' => $form,
                ]);
            } else {
                return $this->redirectToRoute('profil_show', [
                    'id' => $participant->getId(),
                ]);
            }
        }
    }

    /**
     * @Route("/{id}", name="profil_delete", methods={"POST"})
     */
    public function delete(Request $request, Participant $participant): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->getUser()->getAdministrateur() == 1) {
                if ($this->isCsrfTokenValid('delete' . $participant->getId(), $request->request->get('_token'))) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($participant);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('profil_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('profil_show', [
                    'id' => $participant->getId(),
                ]);
            }
        }
    }

    /**
     * @Route("/disable/{id}", name="profil_disable", methods={"POST"})
     */
    public function disable(Request $request, Participant $participant): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->getUser()->getAdministrateur() == 1) {
                if ($this->isCsrfTokenValid('disable' . $participant->getId(), $request->request->get('_token'))) {
                    $participant->setActif(false);
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->flush();
                }
                return $this->redirectToRoute('profil_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('profil_show', [
                    'id' => $participant->getId(),
                ]);
            }
        }
    }

    /**
     * @Route("/enable/{id}", name="profil_enable", methods={"POST"})
     */
    public function enable(Request $request, Participant $participant): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            if ($this->getUser()->getAdministrateur() == 1) {
                if ($this->isCsrfTokenValid('enable' . $participant->getId(), $request->request->get('_token'))) {
                    $participant->setActif(true);
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->flush();
                }
                return $this->redirectToRoute('profil_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('profil_show', [
                    'id' => $participant->getId(),
                ]);
            }
        }
    }


}
