<?php

namespace App\Controller;

use App\Entity\Session;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api/sessions', name: 'api_sessions_')]
final class SessionApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SessionRepository      $repo
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->repo->createQueryBuilder('s');
        if ($name = $request->query->get('name')) {
            $qb->andWhere('s.name LIKE :n')->setParameter('n', "%{$name}%");
        }
        if ($range = $request->query->get('dateRange')) {
            [$from, $to] = explode(' - ', $range);
            $qb->andWhere('s.createdAt BETWEEN :from AND :to')
               ->setParameter('from', new \DateTimeImmutable($from))
               ->setParameter('to',   new \DateTimeImmutable($to));
        }

        $draw   = (int)$request->query->get('draw', 1);
        $start  = (int)$request->query->get('start', 0);
        $length = (int)$request->query->get('length', 10);

        $total           = $this->repo->count([]);
        $recordsFiltered = count($qb->getQuery()->getResult());
        $rows = $qb->orderBy('s.createdAt','DESC')
                   ->setFirstResult($start)
                   ->setMaxResults($length)
                   ->getQuery()
                   ->getArrayResult();

        $data = array_map(fn($r) => [
            'id'        => $r['id'],
            'name'      => $r['name'],
            'createdAt' => $r['createdAt']->format('Y-m-d'),
            'taskCount' => $this->em->getRepository(\App\Entity\Task::class)
                                   ->count(['session'=>$r['id']])
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
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $session = new Session();
        $session->setName($data['name']);
        
        $session->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($session);
        $this->em->flush();

        return $this->json([
            'id'        => $session->getId(),
            'name'      => $session->getName(),
            'createdAt' => $session->getCreatedAt()->format('Y-m-d'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Session $session): JsonResponse
    {
        $this->em->remove($session);
        $this->em->flush();
        return $this->json(['success'=>true]);
    }

    #[Route('/{id}/stats', name: 'sessions_stats', methods: ['GET'])]
    public function stats(int $id): JsonResponse
    {
        $session = $this->em->getRepository(Session::class)->find($id);
        if (!$session) {
            return $this->json(['error'=>'Session non trouvée'], 404);
        }

        $tasks = $this->em->getRepository(\App\Entity\Task::class)->findBy(['session' => $session]);
        $counts = ['total'=>0,'open'=>0,'waiting'=>0,'validated'=>0,'rejected'=>0];
        foreach ($tasks as $t) {
            $counts['total']++;
            switch ($t->getStatus()) {
                case 'open':              $counts['open']++; break;
                case 'waiting_validation':$counts['waiting']++; break;
                case 'validated':         $counts['validated']++; break;
                case 'rejected':          $counts['rejected']++; break;
            }
        }

        return $this->json($counts);
    }

    #[Route('/{id}/tasksbyuser', name: 'api_sessions_tasks_by_user', methods: ['GET'])]
    public function tasksByUser(Session $session): JsonResponse
    {
        $byUser = [];
        foreach ($session->getTasks() as $task) {
            $assignee = $task->getUser();
            $uid      = $assignee ? $assignee->getId() : 'unassigned';
            $byUser[$uid][] = [
                'id'            => $task->getId(),
                'title'         => $task->getTitle(),
                'description'   => $task->getDescription(),
                'deadline'      => $task->getDeadline()?->format('Y-m-d'),
                'urgency'       => $task->getUrgency(),
                'status'        => $task->getStatus(),
                'assigneeId'    => $assignee?->getId(),
                'assigneeName'  => $assignee?->getFullName(),
            ];
        }

        return $this->json([
            'data' => $byUser
        ]);
    }
}
