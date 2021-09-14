<?php

namespace App\Controller;

use App\Entity\Annulation;
use App\Entity\Etat;
use App\Entity\Inscription;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Lieu;
use App\Entity\Ville;
use App\Form\AnnulerType;
use App\Form\LieuType;
use App\Form\SortieType;
use App\Form\VilleType;
use App\Repository\LieuRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use Couchbase\Document;
use http\Client\Curl\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    /**
     * @Route("/", name="sortie_index", methods={"GET"})
     */
    public function index(SortieRepository $sortieRepository, SiteRepository $siteRepository): Response
    {

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->redirectToRoute('app_login');
        } else {
            return $this->render('sortie/index.html.twig', [
                'sorties' => $sortieRepository->findAll(),
                  'sites'=> $siteRepository->findAll(),
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
            $this->addFlash("success","Votre sortie est bien créée !");

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
     * @Route("/{id}/cancel", name="sortie_cancel", methods={"GET","POST"})
     */
    public function cancel(Request $request, Sortie $sortie): Response
    {
        $annulation = new Annulation();
        $form = $this->createForm(AnnulerType::class, $annulation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repo = $this->getDoctrine()->getRepository(Etat::class);
            $etatSuppr = $repo->findOneBy(array('libelle' => 'Annulée'));
            $sortie->setEtat($etatSuppr);
            $sortie->setAnnulation($annulation);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            $this->addFlash("success","Votre sortie a été annulée.");
            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/cancel.html.twig', [
            'sortie' => $sortie,
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
     * @Route("/{id}/join", name="sortie_join", methods={"GET"})
     */
    public function join(Sortie $sortie): Response
    {

            $repo = $this->getDoctrine()->getRepository(Participant::class);
            $id = $this->security->getUser()->getId();
            $participant = $repo->findOneBy(array('id' => $id));
            $inscription = new Inscription();
            $inscription->setDateInscription(new \DateTime("now"));
            $inscription->setSortie($sortie);
            $inscription->setParticipant($participant);
//            dd($inscription);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($inscription);
            $entityManager->flush();
            $this->addFlash("success","Votre inscription a été prise en compte");
        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/quit", name="sortie_quit", methods={"GET"})
     */
    public function quit(Inscription $inscriptionId): Response
    {
        $repo = $this->getDoctrine()->getRepository(Inscription::class);
        $inscription = $repo->find($inscriptionId);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($inscription);
        $entityManager->flush();
        $this->addFlash("success","Votre désinscription a été prise en compte");
        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
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
