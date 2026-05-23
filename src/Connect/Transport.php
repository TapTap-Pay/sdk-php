<?php

declare(strict_types=1);

namespace TapTap\Pay\Connect;

use Google\Protobuf\Internal\Message;
use TapTap\Pay\Options;
use TapTap\Pay\Version;

/**
 * Low-level Connect protocol transport. One per {@see \TapTap\Pay\Client}.
 * Generated <Service>Client classes dispatch through here.
 *
 * Wire format: Connect-protocol unary, application/proto. The proto
 * binary encoding is more compact than JSON and the SDK already ships
 * google/protobuf, so the gains are free. The path is
 * `/{package}.{Service}/{Method}` on the configured baseUrl, matching
 * the spec at https://connectrpc.com/docs/protocol/.
 *
 * Retries: handled inline rather than via an interceptor stack — PHP
 * doesn't have a Connect interceptor ecosystem to integrate with, and
 * a flat loop is easier to reason about for the three knobs we expose
 * (count, base delay, jitter).
 */
final class Transport
{
    private \CurlHandle $curl;
    private string $userAgent;

    public function __construct(private readonly Options $options)
    {
        $this->curl = curl_init();
        $this->userAgent = self::buildUserAgent($options->userAgent);
    }

    public function __destruct()
    {
        if (isset($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * @template T of Message
     * @param string          $path          `/{package}.{Service}/{Method}`
     * @param Message         $request       Request message; will be serialized as proto.
     * @param class-string<T> $responseClass FQCN of the expected response message.
     * @return T
     * @throws Error on any non-success response or transport failure.
     */
    public function call(string $path, Message $request, string $responseClass): Message
    {
        $url = $this->options->baseUrl . $path;
        $body = $request->serializeToString();

        $maxAttempts = max(1, $this->options->maxRetries + 1);
        $lastErr = null;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $raw = $this->send($url, $body);
            } catch (Error $e) {
                $lastErr = $e;
                if (!self::isRetryable($e)) {
                    throw $e;
                }
                if ($attempt + 1 < $maxAttempts) {
                    usleep($this->backoffMicros($attempt));
                    continue;
                }
                throw $e;
            }

            $response = new $responseClass();
            try {
                $response->mergeFromString($raw);
            } catch (\Exception $e) {
                throw new Error(
                    Code::INTERNAL,
                    "failed to decode response into " . $responseClass . ": " . $e->getMessage(),
                    $e,
                );
            }
            return $response;
        }
        throw $lastErr ?? new Error(Code::UNKNOWN, "request failed without a captured error");
    }

    /**
     * Send a single attempt. Caller wraps in the retry loop. Returns
     * the raw response body on 2xx; throws {@see Error} otherwise.
     */
    private function send(string $url, string $body): string
    {
        $headers = [
            "Content-Type: application/proto",
            "Accept: application/proto",
            "Connect-Protocol-Version: 1",
            "Authorization: Bearer " . $this->options->apiKey,
            "User-Agent: " . $this->userAgent,
            "Content-Length: " . strlen($body),
        ];

        curl_reset($this->curl);
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->options->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(30, $this->options->timeoutSeconds),
            CURLOPT_FAILONERROR => false,
        ]);

        $raw = curl_exec($this->curl);
        if ($raw === false) {
            throw new Error(
                Code::UNAVAILABLE,
                "transport error: " . curl_error($this->curl),
            );
        }

        $status = (int) curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $respBody = substr((string) $raw, $headerSize);

        if ($status >= 200 && $status < 300) {
            return $respBody;
        }

        // Connect-protocol error responses carry a JSON body with
        // {"code": "...", "message": "..."} regardless of the request
        // content-type. Fall back to HTTP-status mapping if the body
        // is missing or malformed.
        $code = Code::fromHttpStatus($status);
        $message = "HTTP " . $status;
        if ($respBody !== "") {
            $decoded = json_decode($respBody, true);
            if (is_array($decoded)) {
                if (isset($decoded["code"]) && is_string($decoded["code"])) {
                    $code = $decoded["code"];
                }
                if (isset($decoded["message"]) && is_string($decoded["message"])) {
                    $message = $decoded["message"];
                }
            }
        }
        throw new Error($code, $message);
    }

    /**
     * Connect-protocol codes that warrant a retry. Mirrors the policy
     * the Go and TS SDKs use: transient backpressure and infra hiccups,
     * never policy errors. State-changing RPCs take idempotency_key as
     * a request field, so the proto body is reused verbatim across
     * attempts and the server dedupes naturally.
     */
    private static function isRetryable(Error $e): bool
    {
        return match ($e->connectCode) {
            Code::UNAVAILABLE,
            Code::DEADLINE_EXCEEDED,
            Code::RESOURCE_EXHAUSTED => true,
            default => false,
        };
    }

    /** Full-jitter exponential backoff in microseconds. */
    private function backoffMicros(int $attempt): int
    {
        $maxMs = $this->options->retryBaseDelayMs * (2 ** $attempt);
        $sleepMs = random_int(0, max(1, $maxMs));
        return $sleepMs * 1000;
    }

    private static function buildUserAgent(?string $extra): string
    {
        $parts = [
            "taptap-sdk-php/" . Version::VALUE,
            "(php/" . PHP_VERSION . "; " . PHP_OS . ")",
        ];
        if ($extra !== null && $extra !== "") {
            $parts[] = $extra;
        }
        return implode(" ", $parts);
    }
}
