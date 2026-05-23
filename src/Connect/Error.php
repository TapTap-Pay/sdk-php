<?php

declare(strict_types=1);

namespace TapTap\Pay\Connect;

/**
 * A Connect protocol error. Thrown by {@see Transport::call()} when
 * the server returns a non-2xx response, when a transport-level
 * failure occurs (DNS, TCP, TLS), or when the response body cannot be
 * decoded into the expected message type.
 *
 * Read {@see Error::$connectCode} (or {@see Error::getConnectCode()})
 * for the Connect code — `\Exception::$code` is intentionally left
 * untouched (it stays 0) because `\Exception` declares it non-readonly
 * and PHP won't let a subclass redeclare it as typed/readonly.
 *
 * Most callers should reach for the typed predicates in
 * {@see \TapTap\Pay\Errors} (isNotFound, isAlreadyExists, …) rather
 * than comparing strings directly.
 */
class Error extends \RuntimeException
{
    /** Connect code constant (e.g. {@see Code::NOT_FOUND}). */
    public readonly string $connectCode;

    public function __construct(
        string $connectCode,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
        $this->connectCode = $connectCode;
    }

    public function getConnectCode(): string
    {
        return $this->connectCode;
    }
}
