<?php

declare(strict_types=1);

// Example: mint a payment link, fetch it back, then iterate every
// existing link in the vendor's account.
//
// Run with:
//
//   TAPTAP_API_KEY=taptap_live_... TAPTAP_VENDOR_ID=ven_<uuid> \
//       php examples/quickstart/quickstart.php

require __DIR__ . "/../../vendor/autoload.php";

use Taptap\Common\V1\Money;
use Taptap\Payment\Programmatic\V1\CreatePaymentLinkRequest;
use Taptap\Payment\Programmatic\V1\GetPaymentLinkRequest;
use Taptap\Payment\Programmatic\V1\ListPaymentLinksRequest;
use Taptap\Payment\Programmatic\V1\PaymentLinkUsage;
use TapTap\Pay\Client;
use TapTap\Pay\Connect\Error;
use TapTap\Pay\Errors;
use TapTap\Pay\Idempotency;
use TapTap\Pay\Options;
use TapTap\Pay\Pagination;

$apiKey = getenv("TAPTAP_API_KEY");
$vendorId = getenv("TAPTAP_VENDOR_ID");
if (!$apiKey || !$vendorId) {
    fwrite(STDERR, "set TAPTAP_API_KEY and TAPTAP_VENDOR_ID\n");
    exit(1);
}

$client = new Client(new Options(apiKey: $apiKey));

try {
    $created = $client->paymentLinks->createPaymentLink(
        (new CreatePaymentLinkRequest())
            ->setVendorId($vendorId)
            ->setTitle("Premium plan")
            ->setAmount((new Money())->setCurrency("EUR")->setAmountMinor(2999))
            ->setUsage((new PaymentLinkUsage())->setUnlimited(true))
            ->setIdempotencyKey(Idempotency::newKey()),
    );
} catch (Error $e) {
    fwrite(STDERR, "refused: " . $e->getConnectCode() . " " . (Errors::errorCode($e) ?? "") . "\n");
    exit(1);
}
$linkId = (string) $created->getPaymentLink()?->getId();
echo "created: ", $linkId, PHP_EOL;

$fetched = $client->paymentLinks->getPaymentLink(
    (new GetPaymentLinkRequest())->setVendorId($vendorId)->setPaymentLinkId($linkId),
);
echo "fetched: ", $fetched->getPaymentLink()?->getTitle(), PHP_EOL;

$fetch = function (string $pageToken) use ($client, $vendorId) {
    $resp = $client->paymentLinks->listPaymentLinks(
        (new ListPaymentLinksRequest())
            ->setVendorId($vendorId)
            ->setPageSize(100)
            ->setPageToken($pageToken),
    );
    return [iterator_to_array($resp->getPaymentLinks()), $resp->getNextPageToken()];
};

$count = 0;
foreach (Pagination::items(Pagination::pages($fetch)) as $_link) {
    $count++;
}
echo "total links: ", $count, PHP_EOL;
