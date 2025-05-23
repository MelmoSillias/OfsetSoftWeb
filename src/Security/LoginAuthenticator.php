<?php
// src/Security/LoginFormAuthenticator.php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * 1) Chemin vers la page de login (utilisé par supports() et onFailure/start())
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * 2) Création du Passport pour authentifier l’utilisateur
     */
    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        // Conserver le dernier login en session pour pré-remplir le formulaire
        $request->getSession()
                ->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('password', '')),
            [
                // Vérifie le _csrf_token fourni dans votre formulaire
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    /**
     * 3) En cas de succès : redirection vers la cible ou le dashboard
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Si une URL protégée avait été demandée avant login, on y renvoie :
        if ($target = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($target);
        }

        // Sinon, vers le dashboard
        return new RedirectResponse(
            $this->urlGenerator->generate('app_dashboard')
        );
    }

    /**
     * 4) En cas d’échec : on stocke l’exception en session et on redirige vers la page de login
     *    (AbstractLoginFormAuthenticator s’occupe par défaut de stocker le message d’erreur
     *     dans SecurityRequestAttributes::AUTHENTICATION_ERROR)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // On s’appuie sur l’implémentation parente :
        // - Si $request->hasSession(), elle fera : 
        //     $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        // - Puis RedirectResponse vers getLoginUrl()
        return parent::onAuthenticationFailure($request, $exception);
    }
}
