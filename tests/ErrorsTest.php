<?php

declare(strict_types=1);

namespace TapTap\Pay\Tests;

use PHPUnit\Framework\TestCase;
use Taptap\Common\V1\ErrorInfo;
use TapTap\Pay\Connect\Code;
use TapTap\Pay\Connect\Error;
use TapTap\Pay\Errors;

final class ErrorsTest extends TestCase
{
    /** The JSON body the API answers a refusal with, detail unpadded as Connect sends it. */
    private static function refusalBody(): string
    {
        $info = (new ErrorInfo())
            ->setCode("PAYMENT_LINK_NOT_FOUND")
            ->setParams(["payment_link_id" => "plink_missing"])
            ->setMessage("payment link: not found");
        return (string) json_encode([
            "code" => "not_found",
            "message" => "payment link: not found",
            "details" => [[
                "type" => "taptap.common.v1.ErrorInfo",
                "value" => rtrim(base64_encode($info->serializeToString()), "="),
            ]],
        ]);
    }

    public function testErrorInfoDecodesTheRefusalDetail(): void
    {
        $err = Error::fromHttpResponse(404, self::refusalBody());

        self::assertSame(Code::NOT_FOUND, $err->getConnectCode());
        self::assertSame("payment link: not found", $err->getMessage());
        self::assertTrue(Errors::isNotFound($err));

        $info = Errors::errorInfo($err);
        self::assertNotNull($info);
        self::assertSame("PAYMENT_LINK_NOT_FOUND", $info->getCode());
        self::assertSame(["payment_link_id" => "plink_missing"], iterator_to_array($info->getParams()));
        self::assertSame("PAYMENT_LINK_NOT_FOUND", Errors::errorCode($err));
    }

    public function testErrorInfoIsNullWithoutADetail(): void
    {
        $plain = Error::fromHttpResponse(409, '{"code":"aborted","message":"conflict"}');
        self::assertTrue(Errors::isAborted($plain));
        self::assertSame([], $plain->details);
        self::assertNull(Errors::errorInfo($plain));
        self::assertNull(Errors::errorCode($plain));

        self::assertNull(Errors::errorInfo(new \RuntimeException("dns failure")));
        self::assertNull(Errors::errorInfo(new Error(Code::UNAVAILABLE, "transport error")));
    }

    public function testOtherAndMalformedDetailsAreSkipped(): void
    {
        $body = (string) json_encode([
            "code" => "invalid_argument",
            "message" => "bad",
            "details" => [
                "not an object",
                ["type" => "google.rpc.BadRequest", "value" => "AAAA"],
                ["type" => "taptap.common.v1.ErrorInfo", "value" => "***not base64***"],
                ["type" => "taptap.common.v1.ErrorInfo"],
            ],
        ]);

        $err = Error::fromHttpResponse(400, $body);

        self::assertTrue(Errors::isInvalidArgument($err));
        self::assertCount(1, $err->details);
        self::assertSame("google.rpc.BadRequest", $err->details[0]["type"]);
        self::assertNull(Errors::errorInfo($err));
    }

    public function testANonConnectBodyFallsBackToTheHttpStatus(): void
    {
        $err = Error::fromHttpResponse(503, "<html>bad gateway</html>");

        self::assertSame(Code::UNAVAILABLE, $err->getConnectCode());
        self::assertSame("HTTP 503", $err->getMessage());
        self::assertSame([], $err->details);
    }
}
