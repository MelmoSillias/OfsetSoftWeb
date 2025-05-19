<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


final class UsersController extends AbstractController
{
    #[Route('/dashboard/users', name: 'app_users')]
    public function index(): Response
    {
        return $this->render('users/index.html.twig', [
            'controller_name' => 'UsersController',
        ]);
    }

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function list(Request $request, UserRepository $repo): JsonResponse
    {
        $search = trim($request->query->get('search', ''));
        $role = $request->query->get('role', '');
        $actif = $request->query->get('actif', '');

        // On convertit '0' ou '1' → bool ou null si vide
        $actif = $actif !== '' ? filter_var($actif, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $users = $repo->findAll();
        $data = [];

        foreach ($users as $user) {
            // 🔍 filtre search (nom utilisateur ou email)
            if ($search !== '') {
                $s = strtolower($search);
                if (!str_contains(strtolower($user->getNomUtilisateur()), $s) &&
                    !str_contains(strtolower($user->getEmail()), $s)) {
                    continue;
                }
            }

            // 🔒 filtre role
            if ($role !== '' && !in_array($role, $user->getRoles())) {
                continue;
            }

            // 🔘 filtre actif
            if ($actif !== null && $user->isActif() !== $actif) {
                continue;
            }

            $data[] = [
                'id' => $user->getId(),
                'nom_utilisateur' => $user->getUsername(),
                'nom_complet' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'actif' => $user->isActif(),
            ];
        }

        return $this->json(['data' => $data]);
    }



    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['nom_utilisateur']) || empty($data['fullname']) || empty($data['password']) || empty($data['roles'])) {
            return $this->json(['error' => 'Champs obligatoires manquants.'], 400);
        }

        $user = new User();
        $user->setUsername($data['nom_utilisateur']); 
        $user->setFullname($data['fullname'] ?? $data['nom_utilisateur']);
        $user->setRoles($data['roles']);
        $user->setIsActif($data['actif'] ?? true);
        $user->setPassword($hasher->hashPassword($user, $data['password']));

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur créé']);
    } 

    #[Route('/api/users/{id}/toggle', name: 'api_users_toggle', methods: ['POST'])]
    public function toggle(User $user, EntityManagerInterface $em): JsonResponse
    {
        $user->setIsActif(!$user->isActif());
        $em->flush();

        return $this->json(['message' => 'État modifié', 'actif' => $user->isActif()]);
    }

    #[Route('/api/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
