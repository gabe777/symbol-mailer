<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class AuthorizationService
{
    private const string AUTHORIZATION_HEADER = 'Authorization';
    private const string API_KEY_PARAM = 'api_key';

    /**
     * @var string[]
     */
    private array $validApiKeys;

    public function __construct(string $apiKeys)
    {
        $this->validApiKeys = explode(',', $apiKeys);
    }

    public function authorizeRequest(Request $request): bool
    {
        return $this->isValidApiKey($this->getApiKey($request));
    }

    public function getApiKey(Request $request): ?string
    {
        return $request->headers->get(self::AUTHORIZATION_HEADER) ?? $request->query->get(self::API_KEY_PARAM);
    }

    private function isValidApiKey(?string $apiKey): bool
    {
        return in_array($apiKey, $this->validApiKeys, true);
    }
}