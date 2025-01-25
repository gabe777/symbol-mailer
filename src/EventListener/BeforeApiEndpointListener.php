<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\AuthorizationService;
use App\Service\RateLimiterService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class BeforeApiEndpointListener
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly RateLimiterService $rateLimiterService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_contains($request->getRequestUri(), 'doc') && !$this->authorizationService->authorizeRequest(
                $request
            )) {
            $this->logger->warning('Unauthorized access attempt', [
                'ip' => $request->getClientIp(),
                'path' => $request->getPathInfo(),
            ]);

            $response = new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
            $event->setResponse($response);

            return;
        }

        $isRateLimitExceeded = $this->rateLimiterService->setRateLimiterKey(
            $request->getClientIp().'_'.$this->authorizationService->getApiKey($request)
        )->isLimitExceeded();

        if ($isRateLimitExceeded) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $request->getClientIp(),
                'path' => $request->getPathInfo(),
                'apiKey' => $this->authorizationService->getApiKey($request),
            ]);
            $response = new Response('Rate limit exceeded', Response::HTTP_TOO_MANY_REQUESTS);
            $event->setResponse($response);
        }
    }
}
