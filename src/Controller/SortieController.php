<?php

namespace App\Controller;

use App\Entity\Annulation;
use App\Entity\Etat;
use App\Entity\Inscription;
use App\Entity\Participant;
use App\Entity\Site;
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
use DateTime;
use http\Client\Curl\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @Route("/sortie")
 */
class SortieController extends AbstractController
{
    /**
     * @Route("/", name="sortie_index", methods={"GET"})
     */
    public function index(SortieRepository $sortieRepository, SiteRepository $siteRepository, Request $request): Response
    {

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        } else {
            //Récupération des sorties sans aucun tri
            $sorties = $sortieRepository->findAll();

            //Application des changements d'états en Passée ou Activité en cours si les conditions de date sont réunies
            foreach ($sorties as $sortie) {
                $timeStampDebutSortie = $sortie->getDateDebut()->getTimestamp();
                $timeStampFinSortie = $timeStampDebutSortie + ($sortie->getDuree() * 60);
                if (($timeStampDebutSortie < time()) && (($sortie->getEtat()->getLibelle() == 'Ouverte') || ($sortie->getEtat()->getLibelle() == 'Clôturée') || ($sortie->getEtat()->getLibelle() == 'Activité en cours'))) {

                    if ($timeStampFinSortie < time()) {

                        $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
                        $etat = $repoEtat->findOneBy(array('libelle' => 'Passée'));
                        $sortie->setEtat($etat);
                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($sortie);
                    } else {
                        $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
                        $etat = $repoEtat->findOneBy(array('libelle' => 'Activité en cours'));
                        $sortie->setEtat($etat);
                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($sortie);
                    }
                }
            }

            if ($request->get('keyword') != null ){
                $sorties = $sortieRepository->findByKeyword($request->get('keyword'));
            }

            //Tri par site
            if (($request->get('site')) && ($request->get('site') != "Tous")) {
                $sortieTri = array();
                foreach ($sorties as $sortie) {
                    if ($sortie->getSite()->getNom() == $request->get('site')) {
                        array_push($sortieTri, $sortie);
                    }
                }
                $sorties = $sortieTri;
            }

            //Tri par organisateur
            if ($request->get('organisateur')) {
                $sortieTri = array();
                foreach ($sorties as $sortie) {
                    if ($sortie->getOrganisateur() == $this->getUser()) {
                        array_push($sortieTri, $sortie);
                    }
                }
                $sorties = $sortieTri;
            }


            if ($request->get('passee')) {
                $sortieTri = array();
                foreach ($sorties as $sortie) {
                    $timeStampFinSortie = $sortie->getDateDebut()->getTimestamp() + ($sortie->getDuree() * 60);
                    if ($timeStampFinSortie < time()) {
                        array_push($sortieTri, $sortie);
                    }
                }
                $sorties = $sortieTri;
            }

            if (($request->get('inscrit')) && (!$request->get('nonInscrit'))) {
                $sortieTri = array();
                foreach ($sorties as $sortie) {
                    foreach ($sortie->getInscriptions() as $inscription) {
                        if ($inscription->getParticipant() == $this->getUser()) {
                            array_push($sortieTri, $sortie);
                        }
                    }
                }
                $sorties = $sortieTri;
            }

            if (($request->get('nonInscrit')) && (!$request->get('inscrit'))) {
                $sortieTri = array();
                foreach ($sorties as $sortie) {
                    $inscrit = false;
                    foreach ($sortie->getInscriptions() as $inscription) {
                        if ($inscription->getParticipant() == $this->getUser()) {
                            $inscrit = true;
                        }
                    }
                    if (!$inscrit) {
                        array_push($sortieTri, $sortie);
                    }
                }
                $sorties = $sortieTri;
            }

            if (($request->get('entre') != null) && ($request->get('et') != null)) {
                $sortieTri = array();
                $tsDate1 = strtotime($request->get('entre'));
                $tsDate2 = strtotime($request->get('et')) + 86399; //Pour avoir seulement jusqu'à minuit le jour renseigné et pas au delà, enlever le nombre

                foreach ($sorties as $sortie) {
                    $tsSortie = $sortie->getDateDebut()->getTimestamp();
                    if (($tsSortie > $tsDate1) && ($tsSortie < $tsDate2)) {
                        array_push($sortieTri, $sortie);
                    }
                }
                $sorties = $sortieTri;
            }

            //Tri d'affichage des sorties
            $sortieTriArchive = array();
            foreach ($sorties as $sortie) {
                $timeStampLimiteArchivage = $sortie->getDateDebut()->getTimestamp() + ($sortie->getDuree() * 60) + 2629800;
                if (($timeStampLimiteArchivage > time()) && ($sortie->getEtat()->getLibelle() != 'Supprimée')) {
                    array_push($sortieTriArchive, $sortie);
                }
            }
            $sorties = $sortieTriArchive;


            $this->getDoctrine()->getManager()->flush();
            return $this->render('sortie/index.html.twig', [
                'sorties' => $sorties,
                'sites' => $siteRepository->findAll(),
            ]);
        }
    }

    /**
     * @Route("/new", name="sortie_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        $repo = $this->getDoctrine()->getRepository(Ville::class);
        $villes = $repo->findAll();
        $repo = $this->getDoctrine()->getRepository(Lieu::class);
        $lieu = $repo->findAllPlaces();

        $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
        if (isset($_POST['sortie']['creer'])) {
            $etat = $repoEtat->findOneBy(array('libelle' => 'Créée'));
        } else {
            $etat = $repoEtat->findOneBy(array('libelle' => 'Ouverte'));
        }
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $infosLieu = $serializer->serialize($lieu, 'json');
        if ($form->isSubmitted() && $form->isValid()) {

            $lieuSortie = new Lieu();

            if (isset($_POST['places_to_go'])) {
                $choixSelect = $_POST['places_to_go'];
                foreach ($lieu as $key => $value) {
                    if ($key == $choixSelect) {
                        $place = $repo->findBy(array('id' => $value->getId()));
                        foreach ($place as $key2 => $value2) {
                            $lieuSortie = $value;
                        }
                        break;
                    }
                }
            }

            $inscription = new Inscription();
            $inscription->setDateInscription(new DateTime("now"));
            $inscription->setParticipant($this->getUser());
            $inscription->setSortie($sortie);

            $sortie->setEtat($etat);
            $sortie->setLieu($lieuSortie);
            $sortie->setOrganisateur($this->getUser());
            $sortie->setSite($this->getUser()->getSite());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sortie);
            $entityManager->persist($inscription);
            $entityManager->flush();
            if (isset($_POST['sortie']['creer'])) {
                $this->addFlash("success", "Votre sortie est bien créée !");
            } else {
                $this->addFlash("success", "Votre sortie est bien publiée !");
            }


            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/new.html.twig', [
            'sortie' => $sortie,
            'villes' => $villes,
            'lieux' => $lieu,
            'infosLieu' => $infosLieu,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_show", methods={"GET"})
     */
    public function show(Sortie $sortie, SortieRepository $sortieRepository): Response
    {
        //Tri d'affichage des sorties
        $timeStampLimiteArchivage = $sortie->getDateDebut()->getTimestamp() + ($sortie->getDuree() * 60) + 2629800;

        if ($timeStampLimiteArchivage < time()) {
            //Erreur d'accès
            $this->addFlash("alert", "Erreur. Cette sortie est archivée.");
            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'sorties' => $sortieRepository->findAll(),
        ]);
    }

//    public function index(SortieRepository $sortieRepository, SiteRepository $siteRepository): Response
//    {
//
//        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
//            return $this->redirectToRoute('app_login');
//        } else {
//            if(isset($_POST['select_site']) && $_POST['select_site'].value != "-1") {
//                return $this->render('sortie/index.html.twig', [
//                    'sorties' => $sortieRepository->findBySite(),
//                    'sites'=> $siteRepository->findAll(),
//                ]);
//            }else{
//                return $this->render('sortie/index.html.twig', [
//                    'sorties' => $sortieRepository->findAll(),
//                    'sites'=> $siteRepository->findAll(),
//                ]);
//            }
//        }
//    }

    /**
     * @Route("/{id}/edit", name="sortie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Sortie $sortie): Response
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        $repo = $this->getDoctrine()->getRepository(Ville::class);
        $villes = $repo->findAll();
        $repo = $this->getDoctrine()->getRepository(Lieu::class);
        $lieu = $repo->findAllPlaces();

        $serializer = new Serializer($normalizers, $encoders);
        $infosLieu = $serializer->serialize($lieu, 'json');

        if ($form->isSubmitted() && $form->isValid()) {
            $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
            if (isset($_POST['sortie']['creer'])) {
                $etat = $repoEtat->findOneBy(array('libelle' => 'Créée'));
            } else {
                $etat = $repoEtat->findOneBy(array('libelle' => 'Ouverte'));
            }
            $sortie->setEtat($etat);

            $repoLieuSet = $this->getDoctrine()->getRepository(Lieu::class);
            $lieuSet = $repoLieuSet->findOneBy(array('nom' => $_POST['places_to_go']));
            $sortie->setLieu($lieuSet);
            $this->getDoctrine()->getManager()->flush();
            if (isset($_POST['sortie']['creer'])) {
                $this->addFlash("warning", "Votre sortie est passée en état 'créée'");
            } else {
                $this->addFlash("success", "Votre sortie est passée en état 'publiée'");
            }

            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'villes' => $villes,
            'lieux' => $lieu,
            'infosLieu' => $infosLieu,
        ]);
    }


    /**
     * @Route("/{id}/join", name="sortie_join", methods={"GET"})
     */
    public function join(Sortie $sortie): Response
    {
        $user = $this->getUser();
        if (($sortie->getEtat()->getLibelle() == "Ouverte") && (($sortie->getDateCloture()->getTimestamp() + 86399) >= time())) {
            if ($sortie->getEtat()->getLibelle() != "Clôturée") {
                foreach ($sortie->getInscriptions() as $inscription) {
                    if ($inscription->getParticipant() == $user) {
                        $this->addFlash("alert", "Erreur. Vous êtes déjà inscrit à cette sortie.");
                        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
                    }
                }

                //Ajout d'une inscription
                $repo = $this->getDoctrine()->getRepository(Participant::class);
                $id = $this->getUser()->getId();
                $participant = $repo->findOneBy(array('id' => $id));
                $inscription = new Inscription();
                $inscription->setDateInscription(new \DateTime("now"));
                $inscription->setSortie($sortie);
                $inscription->setParticipant($participant);

                //Changement état en cloturée si plus de place
                if ($sortie->getInscriptions()->count() + 1 >= $sortie->getNbInscriptionsMax()) {
                    $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
                    $etat = $repoEtat->findOneBy(array('libelle' => 'Clôturée'));
                    $sortie->setEtat($etat);
                }

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($inscription);
                $entityManager->persist($sortie);
                $entityManager->flush();
                $this->addFlash("success", "Votre inscription a été prise en compte");
                return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);


            } else {
                $this->addFlash("alert", "Erreur. Cette sortie est déjà complète.");
                return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
            }
        } else {
            $this->addFlash("alert", "Erreur. Les inscriptions pour cette sortie ne sont pas ouvertes.");
            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}/quit", name="sortie_quit", methods={"GET"})
     */
    public function quit(Sortie $sortie): Response
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository(Inscription::class);
        $inscription = $repo->findOneBy(array('sortie' => $sortie, 'Participant' => $user));
        if ($inscription == null) {
            $this->addFlash("alert", "Erreur.Vous n'êtes pas inscrit à cette sortie.");
            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        } else {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($inscription);
            $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
            $etat = $repoEtat->findOneBy(array('libelle' => 'Ouverte'));
            $sortie->setEtat($etat);
            $entityManager->persist($sortie);
            $entityManager->flush();
            $this->addFlash("success", "Votre désinscription a été prise en compte");
            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }
    }

    /**
     * @Route("/{id}/publier", name="sortie_publier", methods={"GET"})
     */
    public function publier(Sortie $sortie): Response
    {
        $repoEtat = $this->getDoctrine()->getRepository(Etat::class);
        $etat = $repoEtat->findOneBy(array('libelle' => 'Ouverte'));
        $sortie->setEtat($etat);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($sortie);
        $entityManager->flush();
        $this->addFlash("success", "Votre sortie a été publiée");
        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
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
            $this->addFlash("success", "Votre sortie a été annulée.");
            return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('sortie/cancel.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="sortie_delete", methods={"POST"})
     */
    public function delete(Request $request, Sortie $sortie): Response
    {
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $repo = $this->getDoctrine()->getRepository(Etat::class);
            $etatSuppr = $repo->findOneBy(array('libelle' => 'Supprimée'));
            $sortie->setEtat($etatSuppr);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            $this->addFlash("success", "Sortie supprimée");
        }
        return $this->redirectToRoute('sortie_index', [], Response::HTTP_SEE_OTHER);
    }
}
