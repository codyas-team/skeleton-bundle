<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class EmailAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;


    private UrlGeneratorInterface $urlGenerator;
    private UserRepository $userRepository;
    private ParameterBagInterface $parameterBag;

    public function __construct(UrlGeneratorInterface $urlGenerator, UserRepository $userRepository, ParameterBagInterface $parameterBag)
    {
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->parameterBag = $parameterBag;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);
        $user = $this->userRepository->findOneBy([
            'email' => $username,
            'enabled' => true,
            'verified' => true,
            'deletedAt' => null
        ]);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }
        $allowedUnverified = ['ROLE_ADMIN'];
        if (empty(array_intersect($allowedUnverified, $user->getRoles()))) {
            throw new CustomUserMessageAuthenticationException('Your email address is not verified. Please check your inbox.');
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $config = $this->parameterBag->get('skeleton');
        return new RedirectResponse($this->urlGenerator->generate($config['templating']['home_path']));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('csk_security_login');
    }
}
