<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Lieu;
use App\Entity\Ville;
use App\Form\LieuType;
use App\Form\SortieType;
use App\Form\VilleType;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use Couchbase\Document;
use http\Client\Curl\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    /**
     * @Route("/", name="sortie_index", methods={"GET"})
     */
    public function index(SortieRepository $sortieRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->redirectToRoute('app_login');
        } else {
            return $this->render('sortie/index.html.twig', [
                'sorties' => $sortieRepository->findAll(),
            ]);
        }
    }

    /**
     * @Route("/new", name="sortie_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $sortie = new Sortie();
        $participant = new Participant();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        $repo = $this->getDoctrine()->getRepository(Lieu::class);
        $lieu = $repo->findAllPlaces();

        $repo2 = $this->getDoctrine()->getRepository(Participant::class);
        $participant = $repo2->findBy(array('id' => $this->getUser()->getId()));

        $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
        $etat = $repoEtat->findOneBy(array('libelle' => 'Créée'));

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $infosLieu = $serializer->serialize($lieu, 'json');

        if ($form->isSubmitted() && $form->isValid()) {
            $lieuSortie = new Lieu();
            $participantOrga = new Participant();
            foreach ($participant as $key => $participantConnecte) {
                $participantOrga = $participantConnecte;
            }
//            dd($participantOrga);

            if(isset($_POST['places_to_go'])){
                $choixSelect = $_POST['places_to_go'];
                foreach ($lieu as $key => $value) {
                    if($key == $choixSelect){
                        $place = $repo->findBy(array('id'=>$value->getId()));
                        foreach ($place as $key2 => $value2) {
                            $lieuSortie = $value;
                        }
                        break;
                    }
                }
            }
            $sortie->setEtat($etat);
            $sortie->setLieu($lieuSortie);
            $sortie->setOrganisateur($participantOrga);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash("message","Votre sortie est bien créée !");

            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/new.html.twig', [
            'sortie' => $sortie,
            'lieux' => $lieu,
            'infosLieu' => $infosLieu,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_show", methods={"GET"})
     */
    public function show(Sortie $sortie): Response
    {
        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="sortie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Sortie $sortie): Response
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        $repo = $this->getDoctrine()->getRepository(Lieu::class);
        $lieu = $repo->findAllPlaces();

        $serializer = new Serializer($normalizers, $encoders);
        $infosLieu = $serializer->serialize($lieu, 'json');

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'lieux' => $lieu,
            'infosLieu' => $infosLieu,
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_delete", methods={"POST"})
     */
    public function delete(Request $request, Sortie $sortie): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sortie->getId(), $request->request->get('_token'))) {
            $repo = $this->getDoctrine()->getRepository(Etat::class);
            $etatSuppr = $repo->findOneBy(array('libelle' => 'Supprimée'));
//            dd($etatSuppr);
            $sortie->setEtat($etatSuppr);
            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
    }
}
