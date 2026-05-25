# TapTap-Pay PHP SDK

[![CI](https://github.com/TapTap-Pay/sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/TapTap-Pay/sdk-php/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-Apache_2.0-blue.svg)](LICENSE)

The official PHP SDK for the [TapTap-Pay](https://usetaptap.com) API.

It wraps the generated protobuf message classes with a thin
[Connect-protocol](https://connectrpc.com/docs/protocol/) transport,
API-key authentication, transient-error retries with exponential
backoff, typed error helpers, and a page iterator for List endpoints.

The SDK exposes only the `programmatic/*` API surface — the API-key
authenticated endpoints meant for server-to-server integrations.

## Install

```bash
composer require taptap-pay/sdk
```

Requires PHP 8.1+ and the `curl` and `json` extensions.

## Quick start

```php
<?php

require __DIR__ . "/vendor/autoload.php";

use Common\V1\Money;
use Programmatic\Payment_links\V1\CreatePaymentLinkRequest;
use TapTap\Pay\Client;
use TapTap\Pay\Options;

$client = new Client(new Options(apiKey: getenv("TAPTAP_SECRET")));

$resp = $client->paymentLinks->createPaymentLink(
    (new CreatePaymentLinkRequest())
        ->setTitle("Premium plan")
        ->setAmount((new Money())->setAmountMinor(2999)->setCurrency("EUR"))
        ->setTargetWalletId(getenv("TAPTAP_WALLET_ID")),
);

echo "link id: ", $resp->getLink()->getId(), PHP_EOL;
```

## Authentication

API keys are minted in the [dashboard](https://app.usetaptap.com). Sandbox
keys are prefixed `sk_test_`, live keys `sk_live_`. The SDK sends them
as `Authorization: Bearer <key>` on every request.

## Configuration

```php
$client = new Client(new Options(
    apiKey: "sk_live_...",           // required
    baseUrl: "https://api.usetaptap.com", // optional override
    maxRetries: 3,                    // default 3
    retryBaseDelayMs: 500,            // default 500ms
    timeoutSeconds: 60,               // default 60s
    userAgent: "my-app/1.4.0",        // optional, appended to SDK UA
));
```

## Idempotency

Every state-changing RPC accepts an `idempotency_key` field on its
request message. The SDK ships a helper to mint a fresh v4 UUID:

```php
use TapTap\Pay\Idempotency;

$req->setIdempotencyKey(Idempotency::newKey());
```

Persist the key client-side **before** sending the request so a
crash-restart can resend the same key and dedupe against the original
result. The retry layer reuses the same proto request across attempts,
so a single caller-side key covers transport-level retries too.

## Error handling

Failures from the transport surface as `TapTap\Pay\Connect\Error`
(a `\RuntimeException`). The `code` property is one of the constants
on `TapTap\Pay\Connect\Code`. Typed predicates in `TapTap\Pay\Errors`
cover the codes integrators are most likely to branch on:

```php
use TapTap\Pay\Connect\Error;
use TapTap\Pay\Errors;

try {
    $resp = $client->payments->getPayment($req);
} catch (Error $e) {
    if (Errors::isNotFound($e)) { /* ... */ }
    if (Errors::isRateLimited($e)) { /* surfaced after retries exhausted */ }
    throw $e;
}
```

## Pagination

List endpoints return 1-indexed pages with a `total_pages` meta. The
`TapTap\Pay\Pagination` helpers walk them lazily:

```php
use Common\V1\PaginationRequestData;
use Programmatic\Payment_links\V1\ListPaymentLinksRequest;
use TapTap\Pay\Pagination;

$fetch = function (int $page) use ($client) {
    $resp = $client->paymentLinks->listPaymentLinks(
        (new ListPaymentLinksRequest())
            ->setPagination((new PaginationRequestData())->setPage($page)->setPageSize(100)),
    );
    return [iterator_to_array($resp->getLinks()), $resp->getMeta()];
};

foreach (Pagination::items(Pagination::pages($fetch)) as $link) {
    echo $link->getId(), PHP_EOL;
}
```

## Versioning

Releases of `taptap-pay/sdk` track the upstream
[TapTap-Pay API](https://github.com/TapTap-Pay/api) tag in lockstep —
SDK version `X.Y.Z` is generated from API tag `X.Y.Z`. The cross-repo
release pipeline regenerates this repo's `gen/` tree on every API
release and pushes a matching tag.

## License

[Apache 2.0](LICENSE).
