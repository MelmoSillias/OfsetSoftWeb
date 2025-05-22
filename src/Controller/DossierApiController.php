<?php

namespace App\Controller;

use App\Entity\CaseDocs;
use App\Entity\ProcessingFile;
use App\Entity\TransferFile;
use App\Entity\Archiving;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\CaseDocsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/api/dossiers', name: 'api_dossiers_')]
class DossierApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CaseDocsRepository     $repo,
        private Environment            $twig,
        private string                 $documentsDirectory
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $draw   = (int)$request->query->get('draw', 1);
        $start  = (int)$request->query->get('start', 0);
        $length = (int)$request->query->get('length', 10);

        $qb = $this->repo->createQueryBuilder('d')
            ->leftJoin('d.owner', 'owner')
            ->addSelect('owner');

        if ($urgency = $request->query->get('urgency')) {
            $qb->andWhere('d.urgency = :urgency')
               ->setParameter('urgency', $urgency);
        }
        if ($status = $request->query->get('status')) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $status);
        }
        if ($range = $request->query->get('dateRange')) {
            [$from, $to] = explode(' - ', $range);
            $qb->andWhere('d.dateReception BETWEEN :from AND :to')
               ->setParameter('from', new \DateTimeImmutable($from))
               ->setParameter('to',   new \DateTimeImmutable($to));
        }

        $total           = $this->repo->count([]);
        $recordsFiltered = count($qb->getQuery()->getResult());
        $rows = $qb
            ->orderBy('d.dateReception', 'DESC')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getArrayResult();

        $data = array_map(fn($r) => [
            'id'            => $r['id'],
            'reference'     => $r['reference'],
            'senderName'    => $r['senderName'],
            'senderContact' => $r['senderContact'],
            'dateReception' => $r['dateReception']->format('Y-m-d'),
            'modeTransmission' => $r['modeTransmission'],
            'urgency'       => $r['urgency'],
            'sender'        => $r['sender'],
            'primaryRecipient' => $r['primaryRecipient_id'] ?? null,
            'owner'         => $r['owner']['FullName'],
            'status'        => $r['status'],
            'generalObservations' => $r['generalObservations'],
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
        $d = new CaseDocs();
        $d->setReference(substr(date('YmdHis') . bin2hex(random_bytes(2)), 0, 10))
          ->setSenderName($request->request->get('senderName', ''))
          ->setSenderContact($request->request->get('senderContact', ''))
          ->setDateReception(new \DateTimeImmutable($request->request->get('dateReception')))
          ->setModeTransmission($request->request->get('modeTransmission', ''))
          ->setUrgency($request->request->get('urgency', 'low'))
          ->setSender($request->request->get('sender', ''))
          ->setStatus('received')
          ->setGeneralObservations($request->request->get('generalObservations', ''));

        if ($id = $request->request->get('primaryRecipient')) {
            $user = $this->em->getRepository(User::class)->find($id);
            $d->setPrimaryRecipient($user);
        }
        if ($id = $request->request->get('owner')) {
            $user = $this->em->getRepository(User::class)->find($id);
            $d->setOwner($user);
        }

        $this->em->persist($d);
        $this->em->flush(); // ID now available

        // Documents upload (multiple files per document)
        $docsData  = $request->request->all('documents', []);
        $docsFiles = $request->files->get('documents', []);
        foreach ($docsData as $i => $docData) {
            $doc = (new Document())
                ->setFolder($d)
                ->setDescription($docData['description'])
                ->setNumberOfCopies((int)$docData['numberOfCopies'])
                ->setNumberOfPages((int)$docData['numberOfPages'])
                ->setDocumentDate(new \DateTimeImmutable($docData['documentDate']))
                ->setSupportingDocuments($docData['supportingDocuments'] ?? '')
                ->setNotes($docData['notes'] ?? '');

            $filenames = [];
            foreach ($docsFiles[$i]['attachedFiles'] ?? [] as $file) {
                $newName = uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move($this->documentsDirectory, $newName);
                    $filenames[] = $newName;
                } catch (FileException) {
                    // optionally log
                }
            }
            $doc->setAttachedFiles($filenames);
            $this->em->persist($doc);
        }
        $this->em->flush();

        return $this->json(['success' => true, 'id' => $d->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'read', methods: ['GET'])]
    public function read(CaseDocs $d): JsonResponse
    {
        return $this->json([
            'id'                    => $d->getId(),
            'reference'             => $d->getReference(),
            'senderName'            => $d->getSenderName(),
            'senderContact'         => $d->getSenderContact(),
            'dateReception'         => $d->getDateReception()->format('Y-m-d'),
            'modeTransmission'      => $d->getModeTransmission(),
            'urgency'               => $d->getUrgency(),
            'sender'                => $d->getSender(),
            'primaryRecipient'      => $d->getPrimaryRecipient()?->getId(),
            'owner'                 => $d->getOwner()?->getId(),
            'status'                => $d->getStatus(),
            'generalObservations'   => $d->getGeneralObservations(),
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, CaseDocs $d): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // Update all editable fields
        foreach ([
            'reference', 'senderName', 'senderContact',
            'modeTransmission', 'urgency', 'sender',
            'generalObservations'
        ] as $field) {
            if (isset($data[$field])) {
                $d->{'set' . ucfirst($field)}($data[$field]);
            }
        }
        if (!empty($data['dateReception'])) {
            $d->setDateReception(new \DateTimeImmutable($data['dateReception']));
        }
        if (!empty($data['primaryRecipient'])) {
            $d->setPrimaryRecipient($this->em->find(User::class, $data['primaryRecipient']));
        }
        if (!empty($data['owner'])) {
            $d->setOwner($this->em->find(User::class, $data['owner']));
        }
        // Status update if provided
        if (isset($data['status'])) {
            $d->setStatus($data['status']);
        }

        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/assign', name: 'assign', methods: ['POST'])]
    public function assign(Request $request, CaseDocs $d): JsonResponse
    {
        $ownerId = json_decode($request->getContent(), true)['owner'] ?? null;
        $user = $this->em->find(User::class, $ownerId);
        $d->setOwner($user);
        $d->setStatus('in_processing');

        $pf = (new ProcessingFile())
            ->setFile($d)
            ->setUser($user)
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('assign')
            ->setObservations('Affectation initiale');
        $this->em->persist($pf);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/reassign', name: 'reassign', methods: ['POST'])]
    public function reassign(Request $request, CaseDocs $d): JsonResponse
    {
        $ownerId = json_decode($request->getContent(), true)['owner'] ?? null;
        $user = $this->em->find(User::class, $ownerId);
        $d->setOwner($user);
        $pf = (new ProcessingFile())
            ->setFile($d)
            ->setUser($user)
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('reassign')
            ->setObservations('Réaffectation');
        $this->em->persist($pf);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/transfer', name: 'transfer', methods: ['POST'])]
    public function transfer(Request $request, CaseDocs $d): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $tf = (new TransferFile())
            ->setFile($d)
            ->setTranferDate(new \DateTimeImmutable($data['date']))
            ->setReason($data['motif'])
            ->setTransferResponsible($this->em->find(User::class, $data['transferResponsible']));
        $this->em->persist($tf);
        // Log ProcessingFile for transfer :contentReference[oaicite:0]{index=0}
        $pf = (new ProcessingFile())
            ->setFile($d)
            ->setUser($tf->getTransferResponsible())
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('transfer')
            ->setObservations($data['reason'] ?? 'Transfert externe');
        $this->em->persist($pf);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/archive', name: 'archive', methods: ['POST'])]
    public function archive(Request $request, CaseDocs $d): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ar = (new Archiving())
            ->setFile($d)
            ->setArchivingDate(new \DateTimeImmutable($data['dateArchiving']))
            ->setWarehouseOffice($data['bureauDepos'])
            ->setArchivist($this->em->find(User::class, $data['archivist']))
            ->setArchivingCoordinate($data['cote'])
            ->setArchivingNotes($data['archivingNotes'] ?? '');
        $this->em->persist($ar);
        // change status to archived
        $d->setStatus('archived');
        // Log ProcessingFile for archive :contentReference[oaicite:1]{index=1}
        $pf = (new ProcessingFile())
            ->setFile($d)
            ->setUser($ar->getArchivist())
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('archive')
            ->setObservations('Archivage');
            
        $this->em->persist($pf);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/export/{format}', name: 'export', methods: ['GET'])]
    public function export(CaseDocs $d, string $format): Response
    {
        if ($format === 'pdf') {
            $html = $this->twig->render('dossier/export.html.twig', ['dossier' => $d]);
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->render();
            $pdf = $dompdf->output();
            return new Response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bordereau_'.$d->getReference().'.pdf"'
            ]);
        }

        // Excel export
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Référence');
        $sheet->setCellValue('B1', $d->getReference());
        $sheet->setCellValue('A2', 'Expéditeur');
        $sheet->setCellValue('B2', $d->getSenderName());
        $sheet->setCellValue('A3', 'Date réception');
        $sheet->setCellValue('B3', $d->getDateReception()->format('Y-m-d'));
        // ... ajouter plus de champs si besoin

        $writer = new Xlsx($spreadsheet);
        $filename = 'bordereau_'.$d->getReference().'.xlsx';
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');
        return $response;
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CaseDocs $d): JsonResponse
    {
        $this->em->remove($d);
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/change-urgency', name: 'change_urgency', methods: ['POST'])]
    public function changeUrgency(Request $request, CaseDocs $dossier): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newUrgency = $data['urgency'] ?? null;
        if (!in_array($newUrgency, ['low','medium','high'], true)) {
            return $this->json(['success'=>false,'error'=>'Valeur d\'urgence invalide'], 400);
        }

        $dossier->setUrgency($newUrgency);

        // Log ProcessingFile
        $user = $this->getUser(); // ou technicien courant
        $pf = (new ProcessingFile())
            ->setFile($dossier)
            ->setUser($user)
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('change_urgency')
            ->setObservations("Urgence passée à $newUrgency");
        $this->em->persist($pf);

        $this->em->flush();

        return $this->json(['success'=>true]);
    }
}
