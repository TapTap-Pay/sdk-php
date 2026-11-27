# TapTap-Pay PHP SDK

[![CI](https://github.com/TapTap-Pay/sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/TapTap-Pay/sdk-php/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-Apache_2.0-blue.svg)](LICENSE)

The official PHP SDK for the [TapTap-Pay](https://usetaptap.com) API.

It wraps the generated protobuf message classes with a thin
[Connect-protocol](https://connectrpc.com/docs/protocol/) transport,
API-key authentication, transient-error retries with exponential
backoff, typed error helpers, and a page iterator for List endpoints.

The SDK exposes only the programmatic API surface (the
`taptap.<context>.programmatic.v1` packages) — the API-key authenticated
endpoints meant for server-to-server integrations.

## Install

```bash
composer require taptap-pay/sdk
```

Requires PHP 8.1+ and the `curl` and `json` extensions.

## Quick start

```php
<?php

require __DIR__ . "/vendor/autoload.php";

use Taptap\Common\V1\Money;
use Taptap\Payment\Programmatic\V1\CreatePaymentLinkRequest;
use Taptap\Payment\Programmatic\V1\PaymentLinkUsage;
use TapTap\Pay\Client;
use TapTap\Pay\Options;

$client = new Client(new Options(apiKey: getenv("TAPTAP_API_KEY")));

$resp = $client->paymentLinks->createPaymentLink(
    (new CreatePaymentLinkRequest())
        ->setVendorId(getenv("TAPTAP_VENDOR_ID"))
        ->setTitle("Premium plan")
        ->setAmount((new Money())->setCurrency("EUR")->setAmountMinor(2999))
        ->setUsage((new PaymentLinkUsage())->setUnlimited(true)),
);

echo "link id: ", $resp->getPaymentLink()?->getId(), PHP_EOL;
```

## Services

| Client property | Service | Namespace of its messages |
|---|---|---|
| `banking` | `BankingService` | `Taptap\Banking\Programmatic\V1` |
| `customers` | `CustomerService` | `Taptap\Payment\Programmatic\V1` |
| `inboundDocuments` | `InboundDocumentService` | `Taptap\Invoicing\Programmatic\V1` |
| `invoices` | `InvoiceService` | `Taptap\Invoicing\Programmatic\V1` |
| `paymentLinks` | `PaymentLinkService` | `Taptap\Payment\Programmatic\V1` |
| `payments` | `PaymentService` | `Taptap\Payment\Programmatic\V1` |
| `refunds` | `RefundService` | `Taptap\Payment\Programmatic\V1` |
| `subvendors` | `SubvendorService` | `Taptap\Marketplace\Programmatic\V1` |
| `transactions` | `TransactionService` | `Taptap\Payment\Programmatic\V1` |
| `wallets` | `WalletService` | `Taptap\Payment\Programmatic\V1` |
| `webhooks` | `WebhookService` | `Taptap\Developer\Programmatic\V1` |

Shared types (`Money`, `PageRequest`, `PageResponse`, `ErrorInfo`) live in
`Taptap\Common\V1`.

## Authentication

API keys are minted in the [dashboard](https://app.usetaptap.com), in your
vendor's settings under API keys. A key looks like `taptap_live_` followed
by a lookup part, a dot and a secret part. The SDK sends it as
`Authorization: Bearer <key>` on every request.

## Vendor id

A key belongs to one vendor, and every request names that vendor in its
`vendor_id` field (`setVendorId()`, `ven_` followed by a UUID). The SDK
does not fill it in: set it on each request. Naming a vendor the key does
not belong to is answered like a vendor that does not exist (`not_found`,
code `VENDOR_NOT_FOUND`).

## Configuration

```php
$client = new Client(new Options(
    apiKey: "taptap_live_...",       // required
    baseUrl: "https://api.usetaptap.com", // optional override
    maxRetries: 3,                    // default 3
    retryBaseDelayMs: 500,            // default 500ms
    timeoutSeconds: 60,               // default 60s
    userAgent: "my-app/1.4.0",        // optional, appended to SDK UA
));
```

## Idempotency

State-changing RPCs accept an `idempotency_key` field on their request
message (`RefundPaymentRequest` calls it `request_key`). The SDK ships a
helper to mint a fresh v4 UUID:

```php
use TapTap\Pay\Idempotency;

$req->setIdempotencyKey(Idempotency::newKey());
```

Persist the key client-side **before** sending the request so a
crash-restart can resend the same key and dedupe against the original
result. The retry layer reuses the same proto request across attempts,
so a single caller-side key covers transport-level retries too. Reusing
a key for a request with different content is refused with `aborted`
and the code `IDEMPOTENCY_KEY_CONFLICT` (`Errors::isAborted()`).

## Error handling

Failures from the transport surface as `TapTap\Pay\Connect\Error`
(a `\RuntimeException`). `getConnectCode()` is one of the constants
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

Every refusal also carries a `taptap.common.v1.ErrorInfo` detail: a
stable machine code, the parameters around it, and an English debug
message. Branch on the code, never on the message:

```php
try {
    $client->refunds->refundPayment($req);
} catch (Error $e) {
    $info = Errors::errorInfo($e);   // ?Taptap\Common\V1\ErrorInfo
    echo $e->getConnectCode(), " ", $info?->getCode(), PHP_EOL;
    if (Errors::errorCode($e) !== "REFUND_EXCEEDS_REMAINDER") {
        throw $e;
    }
}
```

The undecoded details are on `$e->details`, each with the proto type
name and the serialized message bytes.

## Pagination

List endpoints return one page at a time and a cursor for the next. The
`TapTap\Pay\Pagination` helpers walk them lazily. The callback receives
the cursor (empty for the first page) and returns the items and the next
cursor:

```php
use Taptap\Payment\Programmatic\V1\ListPaymentLinksRequest;
use TapTap\Pay\Pagination;

$fetch = function (string $pageToken) use ($client, $vendorId) {
    $resp = $client->paymentLinks->listPaymentLinks(
        (new ListPaymentLinksRequest())
            ->setVendorId($vendorId)
            ->setPageSize(100)
            ->setPageToken($pageToken),
    );
    return [iterator_to_array($resp->getPaymentLinks()), $resp->getNextPageToken()];
};

foreach (Pagination::items(Pagination::pages($fetch)) as $link) {
    echo $link->getId(), PHP_EOL;
}
```

The cursor is spelled three ways across the API, and the callback is
where you map it:

| Lists | Request | Response |
|---|---|---|
| Most (`listPayments`, `listPaymentLinks`, `listRefunds`, `listCustomers`, `listUnappliedReceipts`, `listWalletTransactions`, `listBankTransfers`, `listSubscriptions`, `listDeliveries`) | `setPageToken()` | `getNextPageToken()` |
| `listInvoices`, `listInboundDocuments` | `setCursor()` | `getNextCursor()` |
| `listSubvendors` | `setPage((new PageRequest())->setPageToken(...))` | `getPage()?->getNextPageToken()` |

`listWallets` returns every wallet in one response.

## Versioning

`taptap-pay/sdk` has its own semver tags. After each release of the
[TapTap-Pay monorepo](https://github.com/TapTap-Pay/monorepo), `gen/` is
regenerated from its programmatic protos and pushed here, which cuts the
next SDK release.

## Development

```bash
composer install
composer test
```

## License

[Apache 2.0](LICENSE).
