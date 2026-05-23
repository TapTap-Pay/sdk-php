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
    /** Production TapTap-Pay API endpoint. */
    public const DEFAULT_BASE_URL = "https://api.taptap.rs";

    public readonly string $apiKey;
    public readonly string $baseUrl;
    public readonly int $maxRetries;
    public readonly int $retryBaseDelayMs;
    public readonly int $timeoutSeconds;
    public readonly ?string $userAgent;

    /**
     * @param string      $apiKey           Secret API key (sk_test_… or sk_live_…). Required.
     * @param string|null $baseUrl          Override the API base URL. Defaults to production.
     * @param int|null    $maxRetries       Cap automatic retries on transient errors. Default 3.
     * @param int|null    $retryBaseDelayMs Initial backoff between retries (ms). Default 500.
     * @param int|null    $timeoutSeconds   Per-request HTTP timeout in seconds. Default 60.
     * @param string|null $userAgent        Appended to the SDK's User-Agent header for support attribution.
     */
    public function __construct(
        string $apiKey,
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
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, "/");
        $this->maxRetries = $maxRetries ?? 3;
        $this->retryBaseDelayMs = $retryBaseDelayMs ?? 500;
        $this->timeoutSeconds = $timeoutSeconds ?? 60;
        $this->userAgent = $userAgent;
    }
}
