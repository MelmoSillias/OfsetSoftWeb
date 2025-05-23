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
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Protection;
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

        $qb->andWhere('d.status != :archivedStatus')
           ->setParameter('archivedStatus', "archived");

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
        $d->setStatus('in_processing');
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

    #[Route('/{id}/processing', name: 'processing', methods: ['POST'])]
    public function processing(Request $request, ProcessingFile $pf): JsonResponse
    { 
        $pf->setProcessingNote($request->get('processingNotes') ?? ''); 

        $this->em->flush();
        return $this->json(['success' => true]);  

    }

    #[Route('/{id}/transfer', name: 'transfer', methods: ['POST'])]
    public function transfer(Request $request, CaseDocs $d): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $tf = (new TransferFile())
            ->setFile($d)
            ->setTranferDate(new \DateTimeImmutable())
            ->setReason($data['motif']) 
            ->setTransferResponsible($this->getUser());
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
            ->setArchivingDate(new \DateTimeImmutable())
            ->setWarehouseOffice($data['bureauDepos'])
            ->setArchivist($this->getUser())
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
        $html = $this->twig->render('dossier/export_pdf.html.twig', ['dossier' => $d]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf = $dompdf->output();

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bordereau_' . $d->getReference() . '.pdf"'
        ]);
    }

        // Excel export
       $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ajout du logo
        $logo = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $logo->setPath($this->getParameter('kernel.project_dir') . '/public/assets/img/logo.png');
        $logo->setCoordinates('E1');
        $logo->setWorksheet($sheet);
        $logo->setOffsetX(10);
        $logo->setOffsetY(10);
        $logo->setWidth(150); // Ajuster selon la taille du logo

        // Style général
        $defaultFont = [
            'name' => 'Arial',
            'size' => 10
        ];
        $spreadsheet->getDefaultStyle()->applyFromArray(['font' => $defaultFont]);

        // Style pour le titre principal
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '2F5496']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            ]
        ];

        // Style pour les en-têtes de section
        $sectionHeaderStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2F5496']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ];

        // Style pour les informations clés
        $keyInfoStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1F3864']
            ]
        ];

        // Style pour les bordures
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9']
                ]
            ]
        ];

        // Décalage pour le logo
        $startRow = 5;

        // Entête principal
        $sheet->mergeCells("A{$startRow}:F{$startRow}");
        $sheet->setCellValue("A{$startRow}", 'BORDEREAU D\'EXPORTATION');
        $sheet->getStyle("A{$startRow}")->applyFromArray($titleStyle);
        $startRow++;

        // Informations principales avec mise en forme tabulaire
        $mainInfo = [
            'Référence' => $d->getReference(),
            'Expéditeur' => $d->getSenderName(),
            'Date réception' => $d->getDateReception()->format('d/m/Y'),
            'Mode de transmission' => $d->getModeTransmission(),
            'Urgence' => $d->getUrgency(),
            'Observations générales' => $d->getGeneralObservations()
        ];

        $currentRow = $startRow;
        foreach ($mainInfo as $label => $value) {
            $sheet->setCellValue("A{$currentRow}", $label)
                ->setCellValue("B{$currentRow}", $value)
                ->getStyle("A{$currentRow}")->applyFromArray($keyInfoStyle);
            
            $sheet->getStyle("A{$currentRow}:B{$currentRow}")->applyFromArray([
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
                ]
            ]);
            $currentRow++;
        }

        // Section des documents 

// ... (avant : fusion du titre “DÉTAIL DES DOCUMENTS”)
$currentRow += 2; 
$documentsHeaderRow = $currentRow;
$sheet->mergeCells("A{$documentsHeaderRow}:F{$documentsHeaderRow}");
$sheet->setCellValue("A{$documentsHeaderRow}", 'DÉTAIL DES DOCUMENTS');
$sheet->getStyle("A{$documentsHeaderRow}")->applyFromArray($sectionHeaderStyle);
$currentRow++;

// 1. En-têtes sur une seule ligne
$columns = [
    'Description'           => 40,
    'Nombre de copies'      => 15,
    'Nombre de pages'       => 15,
    'Date du document'      => 18,
    'Documents à l\'appui'  => 30,
    'Notes'                 => 50,
];
$col = 'A';
foreach ($columns as $header => $width) {
    $sheet->setCellValue("{$col}{$currentRow}", $header);
    $sheet->getColumnDimension($col)->setWidth($width);
    $col++;  // passe à la colonne suivante (A → B → C → …)
}
// mise en forme des en-têtes
$lastCol = chr(ord('A') + count($columns) - 1);
$sheet
    ->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")
    ->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType'    => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor'  => ['rgb' => '4472C4'],
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
$currentRow++;

// 2. Données : chaque document sur sa propre ligne
foreach ($d->getDocuments() as $document) {
    $col = 'A';
    // Description
    $sheet->setCellValue("{$col}{$currentRow}", $document->getDescription());
    $col++;
    // Nombre de copies
    $sheet->setCellValue("{$col}{$currentRow}", $document->getNumberOfCopies());
    $col++;
    // Nombre de pages
    $sheet->setCellValue("{$col}{$currentRow}", $document->getNumberOfPages());
    $col++;
    // Date du document
    $dateStr = $document->getDocumentDate()
        ? $document->getDocumentDate()->format('d/m/Y')
        : 'N/A';
    $sheet->setCellValue("{$col}{$currentRow}", $dateStr);
    $col++;
    // Documents à l'appui
    $sheet->setCellValue("{$col}{$currentRow}", $document->getSupportingDocuments());
    $col++;
    // Notes
    $sheet->setCellValue("{$col}{$currentRow}", $document->getNotes());

    // Alignement droite pour les nombres
    $sheet
        ->getStyle("B{$currentRow}:C{$currentRow}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Ex. format conditionnel selon urgence du document
    

    $currentRow++;
}

// Déterminez la dernière colonne utilisée (ex. F si vous avez 6 colonnes)
$lastCol = chr(ord('A') + count($columns) - 1);

// Active l’auto-dimensionnement pour chaque colonne A → dernière
foreach (range('A', $lastCol) as $colLetter) {
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}


// 3. Bordures autour de tout le bloc
$sheet
    ->getStyle("A{$documentsHeaderRow}:F" . ($currentRow - 1))
    ->applyFromArray($borderStyle);


        // Section signature
        $signatureRow = $currentRow + 2;
        $sheet->mergeCells("A{$signatureRow}:B{$signatureRow}");
        $sheet->setCellValue("A{$signatureRow}", 'Signature et cachet du service expéditeur :')
            ->getStyle("A{$signatureRow}")->applyFromArray($keyInfoStyle);

        $sheet->mergeCells("E{$signatureRow}:F{$signatureRow}");
        $sheet->setCellValue("E{$signatureRow}", 'Date d\'exportation : ' . (new \DateTime())->format('d/m/Y'))
            ->getStyle("E{$signatureRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'font' => ['italic' => true]
            ]);

        // Ajustement des hauteurs de ligne
        $sheet->getRowDimension($startRow)->setRowHeight(25);
        $sheet->getRowDimension($documentsHeaderRow)->setRowHeight(20);

        // Protection des cellules
        $sheet->getProtection()->setSheet(true);
        $sheet->getStyle("A{$startRow}:F{$signatureRow}")->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

        $writer = new Xlsx($spreadsheet);
        $filename = 'bordereau_' . $d->getReference() . '.xlsx';
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');

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

    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request, CaseDocs $dossier): JsonResponse
    {
        $dossier->setStatus('validated');
         $user = $this->getUser(); 
        $dossier->setOwner($user);

        $pf = (new ProcessingFile())
            ->setFile($dossier)
            ->setUser($user)
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('validate')
            ->setObservations("Dossier validé");
        $this->em->persist($pf);

        $this->em->flush();

        return $this->json(['success'=>true]);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(Request $request, CaseDocs $dossier): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Raison non spécifiée';

        $dossier->setStatus('rejected');
        $user = $this->getUser(); // ou technicien courant
        $pf = (new ProcessingFile())
            ->setFile($dossier)
            ->setUser($user)
            ->setProcessingDate(new \DateTimeImmutable())
            ->setAction('reject')
            ->setObservations("Dossier rejeté. Raison: $reason");
        $this->em->persist($pf);

        $this->em->flush();

        return $this->json(['success'=>true]);
    } 

}
