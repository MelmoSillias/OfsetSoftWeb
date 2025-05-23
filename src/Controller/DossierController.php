<?php

namespace App\Controller;

use App\Entity\CaseDocs;
use App\Entity\Document;
use App\Entity\ProcessingFile;
use App\Entity\TransferFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/dossiers', name: 'app_dossier_')]
class DossierController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        
        $all = $this->em->getRepository(\App\Entity\User::class)->findAll();
        array_shift($all);  // retire le premier élément du tableau
        $users = $all;
        // La page index est entièrement pilotée en JS (+ DataTable / AJAX)
        return $this->render('dossier/index.html.twig', [
            'controller_name' => 'DossierController',
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(CaseDocs $dossier): Response
    {
        // Pour l’affichage du détail, on récupère aussi documents, historique, transferts
        $documents = $this->em->getRepository(Document::class)
            ->findBy(['folder' => $dossier]);
        $processing = $this->em->getRepository(ProcessingFile::class)
            ->findBy(['file' => $dossier], ['processingDate' => 'ASC']);
        $transfers = $this->em->getRepository(TransferFile::class)
            ->findBy(['file' => $dossier], ['tranferDate' => 'ASC']);
        
        $lastInProcessing = $this->em->getRepository(ProcessingFile::class)
            ->findOneBy(['file' => $dossier, 'action' => ['assign', 'reassign']], ['processingDate' => 'DESC']);
         
            
             $users = $this->em->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->where('u.id != :firstId')
            ->setParameter('firstId', 1)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

  
        return $this->render('dossier/show_dossier.html.twig', [
            'dossier'    => $dossier,
            'documents'  => $documents,
            'history'    => $processing,
            'transfers'  => $transfers,
            'archivings' => [], 
            'controller_name' => 'DossierController',
            'lastInProcessing' => $lastInProcessing,
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CaseDocs $dossier): Response
    {
        // Supprime aussi en cascade les documents (orphanRemoval=true sur Doctrine)
        $this->em->remove($dossier);
        $this->em->flush();

        // Redirection vers l’index
        return $this->redirectToRoute('app_dossier_index');
    }
}

