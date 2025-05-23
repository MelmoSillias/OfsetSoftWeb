<?php

namespace App\Controller;

use App\Entity\CaseDocs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\CaseDocsRepository;
use App\Repository\InvoiceRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    
     #[Route('/dashboard/users/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        // 1. Lecture du JSON
        try {
            $data = $request->toArray();
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Payload JSON invalide.',
            ], 400);
        }

        $old     = $data['oldPassword']     ?? null;
        $new     = $data['newPassword']     ?? null;
        $confirm = $data['confirmPassword'] ?? null;

        // 2. Validation des champs
        if (! $old || ! $new || ! $confirm) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs sont requis.',
            ], 422);
        }
        if (! $passwordHasher->isPasswordValid($user, $old)) {
            return $this->json([
                'success' => false,
                'message' => 'L’ancien mot de passe est incorrect.',
            ], 403);
        }
        if ($new !== $confirm) {
            return $this->json([
                'success' => false,
                'message' => 'La confirmation ne correspond pas.',
            ], 422);
        }

        // 3. Hash & persist
        $user->setPassword(
            $passwordHasher->hashPassword($user, $new)
        );
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.',
        ], 200);
    }

    #[Route('/api/datatable_json_fr', name: 'get_frjson_datatable', methods: ['GET'])]
    public function getDataTableFrJson(): JsonResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/utils/dataTables_fr-FR.json';

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }

        $data = file_get_contents($filePath);
        $jsonData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON format'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($jsonData);
    }

    public function __construct(
        private EntityManagerInterface $em,
        private TaskRepository          $taskRepo,
        private CaseDocsRepository    $folderRepo,
        private InvoiceRepository     $invoiceRepo
    ) {}

    #[Route('/api/dashboard/tasks', name: 'tasks', methods: ['GET'])]
    public function tasks(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error'=>'Non authentifié'], 401);
        }

        $qb = $this->taskRepo->createQueryBuilder('t')
            ->andWhere('t.user = :u')
            ->setParameter('u', $user);

        // filtre dateRange
        if ($dr = $request->query->get('dateRange')) {
            [$from, $to] = explode(' - ', $dr);
            $qb->andWhere('t.deadline BETWEEN :from AND :to')
               ->setParameter('from', new \DateTimeImmutable($from))
               ->setParameter('to',   new \DateTimeImmutable($to));
        }
        // filtre status
        if ($st = $request->query->get('status')) {
            $qb->andWhere('t.status = :st')
               ->setParameter('st', $st);
        }

        $tasks = $qb
            ->orderBy('t.deadline', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Task $t) => [
            'id'       => $t->getId(),
            'title'    => $t->getTitle(),
            'status'   => $t->getStatus(),
            'deadline' => $t->getDeadline()->format('Y-m-d'),
        ], $tasks);

        return $this->json(['tasks' => $data]);
    }

    #[Route('/api/dashboard/folders', name: 'folders', methods: ['GET'])]
    public function folders(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error'=>'Non authentifié'], 401);
        }

        // CaseDocs.owner field holds the assigned user
        $folders = $this->folderRepo->createQueryBuilder('f')
            ->andWhere('f.owner = :u')
            ->setParameter('u', $user)
            ->andWhere('f.status != :s')
            ->setParameter('s', 'archived')
            ->orderBy('f.dateReception', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(CaseDocs $f) => [
            'id'            => $f->getId(),
            'companyName'   => $f->getSenderName(),
            'observations'  => $f->getGeneralObservations(),
            'dateReception' => $f->getDateReception()->format('Y-m-d'),
            'status'        => $f->getStatus(),
        ], $folders);

        return $this->json(['folders' => $data]);
    }

    #[Route('/api/dashboard/invoices', name: 'invoices', methods: ['GET'])]
    public function invoices(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error'=>'Non authentifié'], 401);
        }
        // vérifier rôle si besoin, sinon renvoyer vide
        if (! $this->isGranted('ROLE_FACTURES')) {
            return $this->json(['invoices' => []]);
        }

        $invoices = $this->invoiceRepo->createQueryBuilder('i')
            ->andWhere('i.createdAt >= :from')->setParameter('from', new \DateTime("-5 days")) 
            ->andWhere('i.createdAt <= :to')->setParameter('to', new \DateTime())
            ->andWhere('i.status = :statut')->setParameter('statut', 'impayé')
            ->orderBy('i.createdAt', 'DESC') 
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Invoice $inv) => [
            'id'         => $inv->getId(),
            'number'     => $inv->getRef(),
            'clientName' => $inv->getClient()->getCompanyName(),
            'amount'     => $inv->getAmount(),
            'updatedAt'  => $inv->getUpdatedAt()->format('Y-m-d'),
        ], $invoices);

        return $this->json(['invoices' => $data]);
    }
}
