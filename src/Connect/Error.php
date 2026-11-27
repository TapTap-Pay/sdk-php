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
 * than comparing strings directly, and for
 * {@see \TapTap\Pay\Errors::errorInfo()} to read the stable machine
 * code the API attaches to a refusal.
 */
class Error extends \RuntimeException
{
    /** Connect code constant (e.g. {@see Code::NOT_FOUND}). */
    public readonly string $connectCode;

    /**
     * The error details the server attached, undecoded: the proto
     * type name and the serialized message bytes of each.
     *
     * @var list<array{type: string, value: string}>
     */
    public readonly array $details;

    /**
     * @param list<array{type: string, value: string}> $details
     */
    public function __construct(
        string $connectCode,
        string $message,
        ?\Throwable $previous = null,
        array $details = [],
    ) {
        parent::__construct($message, 0, $previous);
        $this->connectCode = $connectCode;
        $this->details = $details;
    }

    public function getConnectCode(): string
    {
        return $this->connectCode;
    }

    /**
     * Builds the error for a non-2xx response. Connect-protocol error
     * responses carry a JSON body with {"code", "message", "details"}
     * regardless of the request content-type; each detail is
     * {"type": <proto type name>, "value": <base64 message bytes>}.
     * Falls back to HTTP-status mapping when the body is missing or
     * malformed (e.g. a 502 from an upstream proxy).
     */
    public static function fromHttpResponse(int $status, string $body): self
    {
        $code = Code::fromHttpStatus($status);
        $message = "HTTP " . $status;
        $details = [];
        $decoded = $body === "" ? null : json_decode($body, true);
        if (is_array($decoded)) {
            if (isset($decoded["code"]) && is_string($decoded["code"])) {
                $code = $decoded["code"];
            }
            if (isset($decoded["message"]) && is_string($decoded["message"])) {
                $message = $decoded["message"];
            }
            if (isset($decoded["details"]) && is_array($decoded["details"])) {
                $details = self::decodeDetails($decoded["details"]);
            }
        }
        return new self($code, $message, null, $details);
    }

    /**
     * @param array<mixed> $raw
     * @return list<array{type: string, value: string}>
     */
    private static function decodeDetails(array $raw): array
    {
        $details = [];
        foreach ($raw as $detail) {
            if (!is_array($detail) || !is_string($detail["type"] ?? null) || !is_string($detail["value"] ?? null)) {
                continue;
            }
            $value = base64_decode($detail["value"], true);
            if ($value === false) {
                continue;
            }
            $details[] = ["type" => $detail["type"], "value" => $value];
        }
        return $details;
    }
}
