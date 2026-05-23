<?php

declare(strict_types=1);

namespace TapTap\Pay\Connect;

/**
 * Connect protocol error codes. Values mirror the canonical names from
 * https://connectrpc.com/docs/protocol/#error-codes — the wire format
 * is the string name (e.g. "not_found"), which we map to and from
 * these constants in {@see Error}.
 */
final class Code
{
    public const CANCELED = "canceled";
    public const UNKNOWN = "unknown";
    public const INVALID_ARGUMENT = "invalid_argument";
    public const DEADLINE_EXCEEDED = "deadline_exceeded";
    public const NOT_FOUND = "not_found";
    public const ALREADY_EXISTS = "already_exists";
    public const PERMISSION_DENIED = "permission_denied";
    public const RESOURCE_EXHAUSTED = "resource_exhausted";
    public const FAILED_PRECONDITION = "failed_precondition";
    public const ABORTED = "aborted";
    public const OUT_OF_RANGE = "out_of_range";
    public const UNIMPLEMENTED = "unimplemented";
    public const INTERNAL = "internal";
    public const UNAVAILABLE = "unavailable";
    public const DATA_LOSS = "data_loss";
    public const UNAUTHENTICATED = "unauthenticated";

    /**
     * Map an HTTP status code to a Connect Code. Used when the server
     * doesn't return a structured Connect error body (e.g. a 502 from
     * an upstream proxy). The mapping mirrors the Connect spec.
     */
    public static function fromHttpStatus(int $status): string
    {
        return match (true) {
            $status === 400 => self::INVALID_ARGUMENT,
            $status === 401 => self::UNAUTHENTICATED,
            $status === 403 => self::PERMISSION_DENIED,
            $status === 404 => self::UNIMPLEMENTED,
            $status === 408 => self::DEADLINE_EXCEEDED,
            $status === 409 => self::ABORTED,
            $status === 412 => self::FAILED_PRECONDITION,
            $status === 413 => self::RESOURCE_EXHAUSTED,
            $status === 415 => self::INTERNAL,
            $status === 429 => self::UNAVAILABLE,
            $status === 431 => self::RESOURCE_EXHAUSTED,
            $status === 502 => self::UNAVAILABLE,
            $status === 503 => self::UNAVAILABLE,
            $status === 504 => self::UNAVAILABLE,
            $status >= 500 => self::UNKNOWN,
            default => self::UNKNOWN,
        };
    }
}
