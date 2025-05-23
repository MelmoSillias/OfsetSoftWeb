<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TaskController extends AbstractController
{
    #[Route('/dashboard/sessions/{session}/tasks', name:'app_sessions_tasks_index', methods:['GET'])]
    public function index(Session $session): Response
    {
        return $this->render('session/show.html.twig', ['session'=>$session,
            'controller_name' => 'TaskController',
        ]);
    }

    public function __construct(
        private EntityManagerInterface $em,
        private TaskRepository         $repo
    ) {}

    #[Route('/api/sessions/{sessionId}/tasks', name: 'sessions_tasks_list', methods: ['GET'])]
    public function list(int $sessionId, Request $request): JsonResponse
    {
        $session = $this->em->getRepository(Session::class)->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session non trouvée'], 404);
        }

        $qb = $this->repo->createQueryBuilder('t')
            ->andWhere('t.session = :s')
            ->setParameter('s', $session);

        if ($urgency = $request->query->get('urgency')) {
            $qb->andWhere('t.urgency = :urgency')
               ->setParameter('urgency', $urgency);
        }
        if ($status = $request->query->get('status')) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }
        if ($assignee = $request->query->get('assignee')) {
            $qb->andWhere('t.user = :assignee')
               ->setParameter('assignee', $assignee);
        }
        if ($range = $request->query->get('dateRange')) {
            [$from, $to] = explode(' - ', $range);
            $qb->andWhere('t.deadline BETWEEN :from AND :to')
               ->setParameter('from', new \DateTimeImmutable($from))
               ->setParameter('to',   new \DateTimeImmutable($to));
        }
        
        // … mêmes filtres que précédemment …

        $draw   = (int)$request->query->get('draw', 1);
        $start  = (int)$request->query->get('start', 0);
        $length = (int)$request->query->get('length', 10);

        $total           = $this->repo->count(['session' => $session]);
        $recordsFiltered = count($qb->getQuery()->getResult());

        $rows = $qb
            ->orderBy('t.deadline', 'ASC')
            ->join('t.user', 'user')
            ->addSelect('user')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getArrayResult();

        $data = array_map(fn(array $r) => [
            'id'           => $r['id'],
            'title'        => $r['title'],
            'assigneeName' => $r['user']
                ? $this->em->getReference(User::class, $r['user']['id'])->getFullName()
                : '',
            'deadline'     => $r['deadline'] instanceof \DateTimeInterface
                ? $r['deadline']->format('Y-m-d')
                : '',
            'urgency'      => $r['urgency'],
            'status'       => $r['status'],
        ], $rows);

        return $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    #[Route('/api/sessions/{sessionId}/tasks', name: 'sessions_tasks_create', methods: ['POST'])]
    public function create(int $sessionId, Request $request): JsonResponse
    {
        $session = $this->em->getRepository(Session::class)->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $task = new Task();
        $task->setSession($session)
             ->setTitle($data['title'] ?? '')
             ->setDescription($data['description'] ?? '')
             ->setDeadline(new \DateTimeImmutable($data['deadline'] ?? 'now'))
             ->setUrgency($data['urgency'] ?? 'low')
             ->setStatus('open')
             ->setCreatedAt(new \DateTimeImmutable());


        if (!empty($data['assigneeId'])) {
            $assignee = $this->em->getReference(User::class, (int)$data['assigneeId']);
            $task->setUser($assignee);
        }

        $this->em->persist($task);
        $this->em->flush();

        return $this->json(['success' => true, 'id' => $task->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/tasks/{taskId}', name: 'tasks_read', methods: ['GET'])]
    public function read(int $taskId): JsonResponse
    {
        $task = $this->em->getRepository(Task::class)->find($taskId);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        return $this->json([
            'id'           => $task->getId(),
            'sessionId'    => $task->getSession()->getId(),
            'title'        => $task->getTitle(),
            'description'  => $task->getDescription(),
            'deadline'     => $task->getDeadline()->format('Y-m-d'),
            'urgency'      => $task->getUrgency(),
            'status'       => $task->getStatus(),
            'assigneeId'   => $task->getUser()?->getId(),
            'assigneeName' => $task->getUser()?->getFullName(),
        ]);
    }

    #[Route('/api/tasks/{taskId}', name: 'tasks_update', methods: ['PUT'])]
    public function update(int $taskId, Request $request): JsonResponse
    {
        $task = $this->em->getRepository(Task::class)->find($taskId);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        if (!empty($data['deadline'])) {
            $task->setDeadline(new \DateTimeImmutable($data['deadline']));
        }
        if (isset($data['urgency'])) {
            $task->setUrgency($data['urgency']);
        }
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        if (array_key_exists('assignee', $data)) {
            $task->setUser(
                $data['assignee']
                    ? $this->em->getReference(User::class, (int)$data['assigneeId'])
                    : null
            );
        }

        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/tasks/{taskId}/complete', name: 'tasks_complete', methods: ['POST'])]
    public function complete(int $taskId): JsonResponse
    {
        $task = $this->em->getRepository(Task::class)->find($taskId);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        $task->setStatus('waiting_validation');
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/tasks/{taskId}/validate', name: 'tasks_validate', methods: ['POST'])]
    public function validate(int $taskId): JsonResponse
    {
        $task = $this->em->getRepository(Task::class)->find($taskId);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        $task->setStatus('validated');
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/tasks/{taskId}/reject', name: 'tasks_reject', methods: ['POST'])]
    public function reject(int $taskId): JsonResponse
    {
        $task = $this->em->getRepository(Task::class)->find($taskId);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        $task->setStatus('rejected');
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/api/tasks/{taskId}', name: 'tasks_delete', methods: ['DELETE'])]
    public function delete(int $taskId): JsonResponse
    {
        $task = $this->em->getRepository(Task::class)->find($taskId);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        $this->em->remove($task);
        $this->em->flush();
        return $this->json(['success' => true]);
    }
}
