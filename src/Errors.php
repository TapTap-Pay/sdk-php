<?php

declare(strict_types=1);

namespace TapTap\Pay;

use Taptap\Common\V1\ErrorInfo;
use TapTap\Pay\Connect\Code;
use TapTap\Pay\Connect\Error;

/**
 * Typed predicates over {@see Error} for the codes integrators are
 * most likely to branch on. Mirrors the helpers in the Go and TS
 * SDKs so error handling reads the same across languages.
 */
final class Errors
{
    private const ERROR_INFO_TYPE = "taptap.common.v1.ErrorInfo";

    private function __construct()
    {
    }

    public static function isNotFound(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::NOT_FOUND;
    }

    /**
     * AlreadyExists — the resource being created is already there
     * (e.g. a second webhook subscription for the same URL).
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

    /**
     * Aborted — returned with code IDEMPOTENCY_KEY_CONFLICT when an
     * idempotency key is reused for a request whose content differs.
     */
    public static function isAborted(\Throwable $err): bool
    {
        return self::codeOf($err) === Code::ABORTED;
    }

    /**
     * Returns the taptap.common.v1.ErrorInfo detail the API attaches
     * to every refusal: a stable machine code (getCode(), e.g.
     * PAYMENT_LINK_NOT_FOUND), the parameters around it (getParams())
     * and an English debug message (getMessage()). Branch on the code,
     * never on the message. Null when $err is not a Connect error or
     * carries no such detail (e.g. a transport failure).
     */
    public static function errorInfo(\Throwable $err): ?ErrorInfo
    {
        if (!$err instanceof Error) {
            return null;
        }
        foreach ($err->details as $detail) {
            if ($detail["type"] !== self::ERROR_INFO_TYPE) {
                continue;
            }
            $info = new ErrorInfo();
            try {
                $info->mergeFromString($detail["value"]);
            } catch (\Exception) {
                continue;
            }
            return $info;
        }
        return null;
    }

    /** The stable machine code of a refusal, or null without one. */
    public static function errorCode(\Throwable $err): ?string
    {
        return self::errorInfo($err)?->getCode();
    }

    private static function codeOf(\Throwable $err): ?string
    {
        return $err instanceof Error ? $err->connectCode : null;
    }
}
