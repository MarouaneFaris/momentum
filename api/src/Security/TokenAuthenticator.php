<?php

namespace App\Security;

use App\Repository\AuthTokenRepository;
use App\Service\AuthTokenManager;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AuthTokenRepository $repository,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        if ($request->attributes->get('_route') === 'api_login') {
            return false;
        }

        return $request->cookies->has(AuthTokenManager::COOKIE_NAME);
    }

    public function authenticate(Request $request): Passport
    {
        $authToken = $request->cookies->get(AuthTokenManager::COOKIE_NAME);
        $hashedToken = AuthTokenManager::hashToken($authToken);

        return new SelfValidatingPassport(
            new UserBadge($hashedToken, function (string $token) {
                $authToken = $this->repository->findOneBy(['token' => $token]);

                if (!$authToken || $authToken->getExpiresAt() < $this->clock->now()) {
                    throw new AuthenticationException('Invalid or expired token.');
                }

                return $authToken->getUser();
            }),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $response = new JsonResponse(
            ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED,
        );
        $response->headers->setCookie(AuthTokenManager::createClearCookie());

        return $response;
    }
}
