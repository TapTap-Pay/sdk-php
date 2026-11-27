<?php

declare(strict_types=1);

namespace TapTap\Pay;

use Taptap\Banking\Programmatic\V1\BankingServiceClient;
use Taptap\Developer\Programmatic\V1\WebhookServiceClient;
use Taptap\Invoicing\Programmatic\V1\InboundDocumentServiceClient;
use Taptap\Invoicing\Programmatic\V1\InvoiceServiceClient;
use Taptap\Marketplace\Programmatic\V1\SubvendorServiceClient;
use Taptap\Payment\Programmatic\V1\CustomerServiceClient;
use Taptap\Payment\Programmatic\V1\PaymentLinkServiceClient;
use Taptap\Payment\Programmatic\V1\PaymentServiceClient;
use Taptap\Payment\Programmatic\V1\RefundServiceClient;
use Taptap\Payment\Programmatic\V1\TransactionServiceClient;
use Taptap\Payment\Programmatic\V1\WalletServiceClient;
use TapTap\Pay\Connect\Transport;

/**
 * Entry point for the TapTap-Pay SDK.
 *
 * Construct once and reuse — the per-service sub-clients are safe to
 * call concurrently across coroutines/fibers since the shared
 * {@see Transport} keeps state only inside per-call curl invocations.
 *
 * Every request names the vendor the API key belongs to in its
 * vendor_id field (`ven_` followed by a UUID). The SDK does not fill
 * it in.
 *
 * @example
 *   $client = new Client(new Options(apiKey: getenv("TAPTAP_API_KEY")));
 *   $resp = $client->paymentLinks->createPaymentLink(
 *       (new CreatePaymentLinkRequest())
 *           ->setVendorId(getenv("TAPTAP_VENDOR_ID"))
 *           ->setTitle("Premium plan")
 *           ->setAmount((new Money())->setCurrency("EUR")->setAmountMinor(2999))
 *           ->setUsage((new PaymentLinkUsage())->setUnlimited(true)),
 *   );
 *   echo $resp->getPaymentLink()?->getId();
 */
final class Client
{
    public readonly BankingServiceClient $banking;
    public readonly CustomerServiceClient $customers;
    public readonly InboundDocumentServiceClient $inboundDocuments;
    public readonly InvoiceServiceClient $invoices;
    public readonly PaymentLinkServiceClient $paymentLinks;
    public readonly PaymentServiceClient $payments;
    public readonly RefundServiceClient $refunds;
    public readonly SubvendorServiceClient $subvendors;
    public readonly TransactionServiceClient $transactions;
    public readonly WalletServiceClient $wallets;
    public readonly WebhookServiceClient $webhooks;

    public function __construct(Options $options)
    {
        $transport = new Transport($options);
        $this->banking = new BankingServiceClient($transport);
        $this->customers = new CustomerServiceClient($transport);
        $this->inboundDocuments = new InboundDocumentServiceClient($transport);
        $this->invoices = new InvoiceServiceClient($transport);
        $this->paymentLinks = new PaymentLinkServiceClient($transport);
        $this->payments = new PaymentServiceClient($transport);
        $this->refunds = new RefundServiceClient($transport);
        $this->subvendors = new SubvendorServiceClient($transport);
        $this->transactions = new TransactionServiceClient($transport);
        $this->wallets = new WalletServiceClient($transport);
        $this->webhooks = new WebhookServiceClient($transport);
    }
}
