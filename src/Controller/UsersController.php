<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;   
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface; 


final class UsersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}


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
            if ($search !== '') {
                $s = strtolower($search);
                if (!str_contains(strtolower($user->getNomUtilisateur()), $s) &&
                    !str_contains(strtolower($user->getEmail()), $s)) {
                    continue;
                }
            }
 
            if ($role !== '' && !in_array($role, $user->getRoles())) {
                continue;
            } 
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

        if (empty($data['nom_utilisateur']) || empty($data['nom_complet']) || empty($data['password']) || empty($data['roles'])) {
            return $this->json(['error' => 'Champs obligatoires manquants.'], 400);
        }

        $user = new User();
        $user->setUsername($data['nom_utilisateur']); 
        $user->setFullname($data['nom_complet'] ?? $data['nom_utilisateur']);
        $user->setRoles($data['roles']);
        $user->setIsActif($data['actif'] ?? true);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setJobTitle($data['job']); 

        $user->addRole('ROLE_DASHBOARD');

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

    #[Route('/api/users/{user}/permissions', name: 'permissions_read', methods: ['GET'])]
    public function readPermissions(User $user): JsonResponse
    {
        $map = [
          'dashboard'  => 'ROLE_DASHBOARD',
          'sessions'   => 'ROLE_SESSIONS',
          'dossiers'   => 'ROLE_DOSSIERS',
          'archives'   => 'ROLE_ARCHIVES',
          'factures'   => 'ROLE_FACTURES',
          'clients'    => 'ROLE_CLIENTS',
          'commissions'=> 'ROLE_COMMISSIONS',
          'finance'    => 'ROLE_FINANCE',
          'users'      => 'ROLE_USERS',
        ];
        $perms = [];
        foreach ($map as $key => $role) {
            if (in_array($role, $user->getRoles(), true)) {
                $perms[] = $key;
            }
        }
        return $this->json(['permissions' => $perms]);
    }

    // PUT pour mettre à jour
    #[Route('/api/users/{user}/permissions', name: 'permissions_update', methods: ['PUT'])]
    public function updatePermissions(Request $request, User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $selected = $data['permissions'] ?? [];
        $map = [
          'dashboard'  => 'ROLE_DASHBOARD',
          'sessions'   => 'ROLE_SESSIONS',
          'dossiers'   => 'ROLE_DOSSIERS',
          'archives'   => 'ROLE_ARCHIVES',
          'factures'   => 'ROLE_FACTURES',
          'clients'    => 'ROLE_CLIENTS',
          'commissions'=> 'ROLE_COMMISSIONS',
          'finance'    => 'ROLE_FINANCE',
          'users'      => 'ROLE_USERS', 
        ];
        
        $roles = [];
        // Toujours récupérer le premier rôle actuel de l'utilisateur s'il existe
        $currentRoles = $user->getRoles();
        if (!empty($currentRoles)) {
            $roles[] = $currentRoles[0];
        }

        foreach ($selected as $key) {
            if (isset($map[$key])) {
                $roles[] = $map[$key];
            }
        }

        $user->setRoles(array_values(array_unique($roles)));
        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('api/user/theme', name:'theme_update', methods:['PUT'])]
    public function updateTheme(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error'=>'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $theme = $data['theme'] ?? null;
        if (!in_array($theme, ['light','dark'], true)) {
            return $this->json(['error'=>'Theme invalide'], 400);
        }

        $user->setTheme($theme);
        // since $user is already managed, persist() is optional, but harmless
        $this->em->persist($user);
        $this->em->flush();

        return $this->json(['success'=>true,'theme'=>$user->getTheme()]);
    }
}
