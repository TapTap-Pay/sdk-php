<?php

declare(strict_types=1);

// Example: mint a payment link, fetch it back, then iterate every
// existing link in the vendor's account.
//
// Run with:
//
//   TAPTAP_SECRET=sk_test_... TAPTAP_WALLET_ID=<uuid> \
//       php examples/quickstart/quickstart.php

require __DIR__ . "/../../vendor/autoload.php";

use Common\V1\Money;
use Common\V1\PaginationRequestData;
use Programmatic\Payment_links\V1\CreatePaymentLinkRequest;
use Programmatic\Payment_links\V1\GetPaymentLinkRequest;
use Programmatic\Payment_links\V1\ListPaymentLinksRequest;
use TapTap\Pay\Client;
use TapTap\Pay\Options;
use TapTap\Pay\Pagination;

$secret = getenv("TAPTAP_SECRET");
$wallet = getenv("TAPTAP_WALLET_ID");
if (!$secret || !$wallet) {
    fwrite(STDERR, "set TAPTAP_SECRET and TAPTAP_WALLET_ID\n");
    exit(1);
}

$client = new Client(new Options(apiKey: $secret));

$created = $client->paymentLinks->createPaymentLink(
    (new CreatePaymentLinkRequest())
        ->setTitle("Premium plan")
        ->setAmount((new Money())->setAmountMinor(2999)->setCurrency("EUR"))
        ->setTargetWalletId($wallet),
);
echo "created: ", $created->getLink()->getId(), PHP_EOL;

$fetched = $client->paymentLinks->getPaymentLink(
    (new GetPaymentLinkRequest())->setId($created->getLink()->getId()),
);
echo "fetched: ", $fetched->getLink()->getTitle(), PHP_EOL;

$fetch = function (int $page) use ($client) {
    $resp = $client->paymentLinks->listPaymentLinks(
        (new ListPaymentLinksRequest())
            ->setPagination((new PaginationRequestData())->setPage($page)->setPageSize(100)),
    );
    return [iterator_to_array($resp->getLinks()), $resp->getMeta()];
};

$count = 0;
foreach (Pagination::items(Pagination::pages($fetch)) as $_link) {
    $count++;
}
echo "total links: ", $count, PHP_EOL;
