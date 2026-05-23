<?php

declare(strict_types=1);

namespace TapTap\Pay;

use Ramsey\Uuid\Uuid;

/**
 * Idempotency-key helpers. State-changing TapTap-Pay RPCs take
 * `idempotency_key` as a request-message field; the server dedupes
 * on it, so persisting one client-side before sending the request
 * lets a crash-restart resend the same key and reach the same result.
 */
final class Idempotency
{
    private function __construct()
    {
    }

    /** Fresh UUID v4 suitable for use as an idempotency_key. */
    public static function newKey(): string
    {
        return Uuid::uuid4()->toString();
    }
}
