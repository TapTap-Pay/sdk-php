# Changelog

All notable changes to the TapTap-Pay PHP SDK are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
`gen/` is regenerated from the
[TapTap-Pay monorepo](https://github.com/TapTap-Pay/monorepo) after each of
its releases.

## [Unreleased]

### Changed (breaking)

- The SDK now targets the contract the monorepo generates: proto packages
  `taptap.<context>.programmatic.v1`, PHP namespaces
  `Taptap\<Context>\Programmatic\V1` and `Taptap\Common\V1`.
- `Client` properties follow the new services: `banking`, `customers`,
  `inboundDocuments`, `invoices`, `paymentLinks`, `payments`, `refunds`,
  `subvendors`, `transactions`, `wallets`, `webhooks`. `payIns`, `payOuts`
  and `transfers` are gone; payouts are read through `banking`.
- Every request names the vendor in `vendor_id`.
- Pagination is cursor based: the `Pagination::pages()` callback takes a
  page token and returns `[items, nextPageToken]`, and `Page` carries
  `nextPageToken` instead of `meta`.

### Added

- `Errors::errorInfo()` and `Errors::errorCode()` decode the
  `taptap.common.v1.ErrorInfo` detail attached to every refusal;
  `Connect\Error::$details` holds the undecoded details.
- `Errors::isAborted()`, for an idempotency key reused with different
  content.
- A PHPUnit suite (`composer test`).
