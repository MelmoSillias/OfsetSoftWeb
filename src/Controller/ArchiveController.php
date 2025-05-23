<?php

namespace App\Controller;

use App\Entity\Archiving;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArchiveController extends AbstractController
{
    private $em;

    public function __construct(\Doctrine\ORM\EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/dashboard/archive', name: 'app_archive')]
    public function index(): Response
    {
        $users = $this->em->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->where('u.id != :firstId')
            ->setParameter('firstId', 1)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('archive/index.html.twig', [
            'controller_name' => 'ArchiveController',
            'users' => $users,
        ]);
    }
    #[Route('/dashboard/archive/{id}', name: 'show', methods: ['GET'])]
    public function show(Archiving $archiving): Response
    {
        // On renvoie les données JSON pour le modal “Voir”
        return $this->json([
            'id'             => $archiving->getId(),
            'reference'      => $archiving->getFile()->getReference(),
            'dateReception'  => $archiving->getFile()->getDateReception()->format('Y-m-d'),
            'dateArchiving'  => $archiving->getArchivingDate()->format('Y-m-d'),
            'bureauDepos'    => $archiving->getWarehouseOffice(),
            'archivistId'    => $archiving->getArchivist()->getId(),
            'archivistName'  => $archiving->getArchivist()->getFullName(),
            'cote'           => $archiving->getArchivingCoordinate(),
            'archivingNotes' => $archiving->getArchivingNotes(),
        ]);
    }
}
