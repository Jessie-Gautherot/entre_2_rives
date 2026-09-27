<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authenticator used to log in users.
 *
 * Responsibilities:
 * - Get login form data
 * - Identify user by email
 * - Get password for verification
 * - Protect the login form with a CSRF token
 * - Handle remember me
 * - Redirect the user after login
 */
class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private RouterInterface $router
    ) {
    }

    /**
     * Handle login form submission.
     */
    public function authenticate(Request $request): Passport
    {
        // Get login data from the form.
        $email = strtolower(trim(
            (string) $request->request->get('email', '')
        ));

        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        // Save the last entered email.
        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email),

            // Symfony checks the password automatically.
            new PasswordCredentials($password),

            [
                // Protect the form against CSRF attacks.
                new CsrfTokenBadge('authenticate', $csrfToken),

                // Allow the user to stay connected.
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * Redirect the user after successful authentication.
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Redirect to the requested page if available.
        $targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        );

        if ($targetPath !== null) {
            return new RedirectResponse($targetPath);
        }

        // Default redirection to the home page.
        return new RedirectResponse(
            $this->router->generate('app_home')
        );
    }

    /**
     * Return the login page URL.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}