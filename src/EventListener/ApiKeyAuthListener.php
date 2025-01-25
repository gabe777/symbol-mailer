<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApiKeyAuthListener
{
    private const string AUTHORIZATION_HEADER = 'Authorization';
    private const string API_KEY_PARAM = 'api_key';
    private array $validApiKeys;

    public function __construct(
        string $apiKeys,
        private readonly LoggerInterface $logger
    ) {
        $this->validApiKeys = explode(',', $apiKeys);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $apiKey = $request->headers->get(self::AUTHORIZATION_HEADER) ?? $request->query->get(self::API_KEY_PARAM);

        if (!str_contains($request->getRequestUri(), 'doc') && !$this->isValidApiKey($apiKey)) {
            $this->logger->warning('Unauthorized access attempt', [
                'ip' => $request->getClientIp(),
                'path' => $request->getPathInfo(),
            ]);

            $response = new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
            $event->setResponse($response);
        }
        // @todo Rate limiting!
    }

    private function isValidApiKey(?string $apiKey): bool
    {
        return in_array($apiKey, $this->validApiKeys, true);
    }
}
