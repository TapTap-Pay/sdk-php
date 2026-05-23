<?php

declare(strict_types=1);

namespace TapTap\Pay;

use Programmatic\Invoices\V1\InvoicesServiceClient;
use Programmatic\Payins\V1\PayInsServiceClient;
use Programmatic\Payment_links\V1\PaymentLinksServiceClient;
use Programmatic\Payments\V1\PaymentsServiceClient;
use Programmatic\Payouts\V1\PayOutsServiceClient;
use Programmatic\Refunds\V1\RefundsServiceClient;
use Programmatic\Transactions\V1\TransactionsServiceClient;
use Programmatic\Transfers\V1\TransfersServiceClient;
use Programmatic\Wallets\V1\WalletsServiceClient;
use Programmatic\Webhooks\V1\WebhooksServiceClient;
use TapTap\Pay\Connect\Transport;

/**
 * Entry point for the TapTap-Pay SDK.
 *
 * Construct once and reuse — the per-service sub-clients are safe to
 * call concurrently across coroutines/fibers since the shared
 * {@see Transport} keeps state only inside per-call curl invocations.
 *
 * @example
 *   $client = new Client(new Options(apiKey: getenv("TAPTAP_SECRET")));
 *   $resp = $client->paymentLinks->createPaymentLink(
 *       (new CreatePaymentLinkRequest())
 *           ->setTitle("Premium plan")
 *           ->setAmount((new Money())->setAmountMinor(2999)->setCurrency("EUR"))
 *           ->setTargetWalletId(getenv("TAPTAP_WALLET_ID")),
 *   );
 *   echo $resp->getLink()->getId();
 */
final class Client
{
    public readonly InvoicesServiceClient $invoices;
    public readonly PayInsServiceClient $payIns;
    public readonly PaymentLinksServiceClient $paymentLinks;
    public readonly PaymentsServiceClient $payments;
    public readonly PayOutsServiceClient $payOuts;
    public readonly RefundsServiceClient $refunds;
    public readonly TransactionsServiceClient $transactions;
    public readonly TransfersServiceClient $transfers;
    public readonly WalletsServiceClient $wallets;
    public readonly WebhooksServiceClient $webhooks;

    public function __construct(Options $options)
    {
        $transport = new Transport($options);
        $this->invoices = new InvoicesServiceClient($transport);
        $this->payIns = new PayInsServiceClient($transport);
        $this->paymentLinks = new PaymentLinksServiceClient($transport);
        $this->payments = new PaymentsServiceClient($transport);
        $this->payOuts = new PayOutsServiceClient($transport);
        $this->refunds = new RefundsServiceClient($transport);
        $this->transactions = new TransactionsServiceClient($transport);
        $this->transfers = new TransfersServiceClient($transport);
        $this->wallets = new WalletsServiceClient($transport);
        $this->webhooks = new WebhooksServiceClient($transport);
    }
}
