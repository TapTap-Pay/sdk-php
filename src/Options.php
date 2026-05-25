<?php

declare(strict_types=1);

namespace TapTap\Pay;

/**
 * Configuration for {@see Client}. The constructor enforces the only
 * hard requirement (apiKey) and fills sensible defaults for
 * everything else.
 */
final class Options
{
    // -- Environment URLs --------------------------------------------------
    // CI rewrites these constants from secrets at release time so every
    // published SDK version carries the correct hosts. Consumers pick
    // an environment via the `$mode` constructor param ('production' or
    // 'sandbox'); the base URL resolves automatically.

    public const PROD_BASE_URL = 'https://api.usetaptap.com';
    public const SANDBOX_BASE_URL = 'https://api.usetaptap.dev';

    public readonly string $apiKey;
    public readonly string $baseUrl;
    public readonly string $mode;
    public readonly int $maxRetries;
    public readonly int $retryBaseDelayMs;
    public readonly int $timeoutSeconds;
    public readonly ?string $userAgent;

    /**
     * @param string      $apiKey           Secret API key. Required.
     * @param string      $mode             'production' (default) or 'sandbox'.
     * @param string|null $baseUrl          Explicit override — ignores $mode when set.
     * @param int|null    $maxRetries       Cap automatic retries on transient errors. Default 3.
     * @param int|null    $retryBaseDelayMs Initial backoff between retries (ms). Default 500.
     * @param int|null    $timeoutSeconds   Per-request HTTP timeout in seconds. Default 60.
     * @param string|null $userAgent        Appended to the SDK's User-Agent header for support attribution.
     */
    public function __construct(
        string $apiKey,
        string $mode = 'production',
        ?string $baseUrl = null,
        ?int $maxRetries = null,
        ?int $retryBaseDelayMs = null,
        ?int $timeoutSeconds = null,
        ?string $userAgent = null,
    ) {
        if ($apiKey === "") {
            throw new \InvalidArgumentException("TapTap: apiKey is required");
        }
        $this->apiKey = $apiKey;
        $this->mode = $mode;

        if ($baseUrl !== null) {
            $this->baseUrl = rtrim($baseUrl, "/");
        } else {
            $this->baseUrl = $mode === 'sandbox'
                ? rtrim(self::SANDBOX_BASE_URL, "/")
                : rtrim(self::PROD_BASE_URL, "/");
        }

        $this->maxRetries = $maxRetries ?? 3;
        $this->retryBaseDelayMs = $retryBaseDelayMs ?? 500;
        $this->timeoutSeconds = $timeoutSeconds ?? 60;
        $this->userAgent = $userAgent;
    }
}
