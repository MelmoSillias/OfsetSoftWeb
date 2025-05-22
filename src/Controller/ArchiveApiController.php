<?php

namespace App\Controller;

use App\Entity\Archiving;
use App\Repository\ArchivingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/archives', name: 'api_archives_')]
final class ArchiveApiController extends AbstractController
{
    
    public function __construct(
        private EntityManagerInterface $em,
        private ArchivingRepository     $repo
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->repo->createQueryBuilder('a')
            ->join('a.file', 'd')->addSelect('d');

        if ($range = $request->query->get('dateRange')) {
            [$from, $to] = explode(' - ', $range);
            $qb->andWhere('a.archivingDate BETWEEN :from AND :to')
               ->setParameter('from', new \DateTimeImmutable($from))
               ->setParameter('to',   new \DateTimeImmutable($to));
        }
        if ($archivist = $request->query->get('archivist')) {
            $qb->andWhere('a.archivist = :u')->setParameter('u', $archivist);
        }
        if ($bureau = $request->query->get('bureauDepos')) {
            $qb->andWhere('a.warehouseOffice LIKE :b')->setParameter('b', "%{$bureau}%");
        }

        $draw   = (int)$request->query->get('draw', 1);
        $start  = (int)$request->query->get('start', 0);
        $length = (int)$request->query->get('length', 10);

        $total           = $this->repo->count([]);
        $recordsFiltered = count($qb->getQuery()->getResult());
        $rows = $qb
            ->orderBy('a.archivingDate', 'DESC')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getArrayResult();

        $data = array_map(fn(array $r) => [
            'id'             => $r['id'],
            'reference'      => $r['d']['reference'],
            'dateReception'  => (new \DateTimeImmutable($r['d']['dateReception']))->format('Y-m-d'),
            'dateArchiving'  => $r['archivingDate']->format('Y-m-d'),
            'bureauDepos'    => $r['warehouseOffice'],
            'archivistName'  => $r['archivist_fullName'], // ajoutez cet alias en DQL si nécessaire
            'cote'           => $r['archivingCoordinate'],
            'archivingNotes' => $r['archivingNotes'],
        ], $rows);

        return $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dId    = $request->request->getInt('dossierId');
        $date   = new \DateTimeImmutable($request->request->get('dateArchiving'));
        $bureau = $request->request->get('bureauDepos', '');
        $archiv = $request->request->getInt('archivist');
        $cote   = $request->request->get('cote', '');
        $notes  = $request->request->get('archivingNotes', '');

        $dossier   = $this->em->getReference(\App\Entity\CaseDocs::class, $dId);
        $archiving = (new Archiving())
            ->setFile($dossier)
            ->setArchivingDate($date)
            ->setWarehouseOffice($bureau)
            ->setArchivist($this->em->getReference(\App\Entity\User::class, $archiv))
            ->setArchivingCoordinate($cote)
            ->setArchivingNotes($notes);

        $this->em->persist($archiving);
        $dossier->setStatus('archived');
        $this->em->flush();

        return $this->json(['success' => true, 'id' => $archiving->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'read', methods: ['GET'])]
    public function read(Archiving $archiving): JsonResponse
    {
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

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Archiving $archiving): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['dateArchiving'])) {
            $archiving->setArchivingDate(new \DateTimeImmutable($data['dateArchiving']));
        }
        if (isset($data['bureauDepos'])) {
            $archiving->setWarehouseOffice($data['bureauDepos']);
        }
        if (!empty($data['archivist'])) {
            $archiving->setArchivist(
                $this->em->getReference(\App\Entity\User::class, (int)$data['archivist'])
            );
        }
        if (isset($data['cote'])) {
            $archiving->setArchivingCoordinate($data['cote']);
        }
        if (isset($data['archivingNotes'])) {
            $archiving->setArchivingNotes($data['archivingNotes']);
        }

        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Archiving $archiving): JsonResponse
    {
        $this->em->remove($archiving);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/export/{format}', name: 'export', methods: ['GET'])]
    public function export(Archiving $archiving, string $format): Response
    {
        // Implémentation PDF/Excel identique au DossierApiController
        throw $this->createNotFoundException();
    }
}
