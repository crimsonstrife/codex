<?php

    namespace App\Services;

    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Http;
    use Log;
    use Throwable;

    /**
     * Service for communicating with the Forge system API using machine-to-machine OAuth.
     *
     * This class:
     * - Validates Forge integration configuration
     * - Obtains and caches a client-credentials access token
     * - Fetches project collections and single projects from Forge
     * - Handles non-2xx responses and transport failures with logging
     */
    class ForgeService
    {
        /**
         * Base Forge URL (without trailing slash), for example: https://forge.example.com.
         */
        protected string $baseUrl;

        /**
         * OAuth client ID for machine-to-machine access.
         */
        protected ?string $clientId;

        /**
         * OAuth client secret for machine-to-machine access.
         */
        protected ?string $clientSecret;

        /**
         * Initialize Forge API configuration from app config.
         *
         * Reads:
         * - codex.forge.url
         * - codex.forge.m2m_client_id
         * - codex.forge.m2m_client_secret
         */
        public function __construct()
        {
            $this->baseUrl      = rtrim(config('codex.forge.url', ''), '/');
            $this->clientId     = config('codex.forge.m2m_client_id');
            $this->clientSecret = config('codex.forge.m2m_client_secret');
        }

        /**
         * Determine whether all required Forge M2M configuration values are present.
         *
         * @return bool True when URL, client ID, and client secret are all non-empty.
         */
        public function isConfigured(): bool
        {
            return ! empty($this->baseUrl)
                && ! empty($this->clientId)
                && ! empty($this->clientSecret);
        }

        /**
         * Obtain a client credentials access token from Forge, caching it for 23 hours.
         * Passport client_credentials tokens default to a 1-year TTL, so this is safe.
         * The cache is keyed by client ID so rotating the client invalidates it immediately.
         *
         * @return string|null OAuth bearer token, or null if token retrieval failed.
         */
        protected function getAccessToken(): ?string
        {
            $cacheKey = 'forge_service.access_token.' . md5($this->clientId ?? '');

            return Cache::remember($cacheKey, now()->addHours(23), function () {
                $response = Http::withoutVerifying()
                    ->asForm()
                    ->timeout(10)
                    ->post($this->baseUrl . '/oauth/token', [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope'         => 'projects:read',
                    ]);

                if (! $response->successful()) {
                    Log::warning('ForgeService: failed to obtain client credentials token', [
                        'status' => $response->status(),
                        'body'   => substr($response->body(), 0, 500),
                    ]);
                    return null;
                }

                return $response->json('access_token');
            });
        }

        /**
         * Return Forge projects visible to a specific user.
         *
         * Passing a $forgeUserId scopes the results to projects that user is a
         * member of. If null (the Codex user has not linked their Forge account),
         * an empty array is returned immediately - we never show projects the
         * requesting user doesn't have access to.
         *
         * @param string|null $forgeUserId Forge user identifier used for access scoping.
         * @return array<int, array<string, mixed>>
         */
        public function getProjects(?string $forgeUserId = null): array
        {
            if (! $this->isConfigured()) {
                Log::warning('ForgeService::getProjects called but service is not configured', [
                    'baseUrl'      => $this->baseUrl,
                    'clientId'     => $this->clientId ? 'set' : 'missing',
                    'clientSecret' => $this->clientSecret ? 'set' : 'missing',
                ]);
                return [];
            }

            // If no Forge user identity is available, we cannot determine which
            // projects this person can access - return nothing rather than everything.
            if ($forgeUserId === null) {
                return [];
            }

            $path = '/api/v1/system/projects?' . http_build_query(['for_user' => $forgeUserId]);
            $response = $this->get($path);

            // API returns a paginated resource: {"data": [...], "links": {...}, "meta": {...}}
            return $response['data'] ?? $response;
        }

        /**
         * Fetch a single Forge project by ID.
         *
         * @param string $id Forge project ID.
         * @return array<string, mixed>|null Project payload, or null when unavailable/unconfigured.
         */
        public function getProject(string $id): ?array
        {
            if (! $this->isConfigured()) {
                return null;
            }

            return $this->get("/api/v1/system/projects/{$id}") ?: null;
        }

        /**
         * Perform an authenticated GET request against the Forge API.
         *
         * Behavior:
         * - Returns decoded JSON for successful responses
         * - Clears cached token on 401/403 so next call refreshes credentials
         * - Logs non-2xx responses and exceptions
         * - Returns empty array on any failure path
         *
         * @param string $path API path including leading slash and optional query string.
         * @return array<string, mixed>
         */
        protected function get(string $path): array
        {
            $token = $this->getAccessToken();

            if (! $token) {
                return [];
            }

            $url = $this->baseUrl . $path;

            try {
                $response = Http::withToken($token)
                    ->timeout(10)
                    ->withoutVerifying()
                    ->get($url);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                // If the token was rejected (401/403), clear the cache so it's
                // refreshed on the next request rather than replaying a bad token.
                if (in_array($response->status(), [401, 403], true)) {
                    Cache::forget('forge_service.access_token.' . md5($this->clientId ?? ''));
                }

                Log::warning('ForgeService: non-2xx response', [
                    'url'    => $url,
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 500),
                ]);
            } catch (Throwable $e) {
                Log::error('ForgeService: request failed', [
                    'url'   => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        }
    }
