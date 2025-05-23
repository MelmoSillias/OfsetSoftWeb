<?php

namespace App\Controller;

use App\Entity\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class SessionController extends AbstractController
{

    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/dashboard/session', name: 'app_session')]
    public function index(): Response
    {

        return $this->render('session/index.html.twig', [
            'controller_name' => 'SessionController',
        ]);
    }
    #[Route('/dashboard/session/{id}', name: 'show', methods: ['GET'])]
    public function show(Session $session, EntityManagerInterface $em): Response
    {
        // Affiche la liste des tâches de la session

        // Example: fetch all users (replace 'User' with your actual User entity)
       $users = $this->em->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->where('u.id != :firstId')
            ->setParameter('firstId', 1)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();


        return $this->render('session/session-show.html.twig', [
            'session' => $session,
            'users' => $users,
            'controller_name' => 'SessionController'
        ]); 
    }


}
