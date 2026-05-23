<?php

declare(strict_types=1);

namespace TapTap\Pay;

use TapTap\Pay\Connect\Code;
use TapTap\Pay\Connect\Error;

/**
 * Typed predicates over {@see Error} for the codes integrators are
 * most likely to branch on. Mirrors the helpers in the Go and TS
 * SDKs so error handling reads the same across languages.
 */
final class Errors
{
    private function __construct()
    {
    }

    public static function isNotFound(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::NOT_FOUND;
    }

    /**
     * AlreadyExists — typically returned when an idempotency_key
     * collides with a prior request whose payload differed.
     */
    public static function isAlreadyExists(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::ALREADY_EXISTS;
    }

    /** InvalidArgument — usually a protovalidate failure. */
    public static function isInvalidArgument(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::INVALID_ARGUMENT;
    }

    /** PermissionDenied — the API key lacks scope for this resource. */
    public static function isPermissionDenied(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::PERMISSION_DENIED;
    }

    /** Unauthenticated — missing or invalid API key. */
    public static function isUnauthenticated(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::UNAUTHENTICATED;
    }

    /**
     * ResourceExhausted — the SDK's retry budget held even after
     * backoff. Surface a "try again later" path to the user.
     */
    public static function isRateLimited(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::RESOURCE_EXHAUSTED;
    }

    /**
     * FailedPrecondition — request was well-formed but the resource
     * is in the wrong state (e.g. refunding an already-refunded payment).
     */
    public static function isFailedPrecondition(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::FAILED_PRECONDITION;
    }

    private static function codeOf(\Throwable $err): ?string
    {
        return $err instanceof Error ? $err->connectCode : null;
    }
}
