<?php

declare(strict_types=1);

namespace TapTap\Pay\Tests;

use PHPUnit\Framework\TestCase;
use TapTap\Pay\Client;
use TapTap\Pay\Options;

final class ClientTest extends TestCase
{
    public function testEveryServiceClientIsWired(): void
    {
        $client = new Client(new Options(apiKey: "taptap_live_000000000000.test"));

        $services = [
            "banking" => ["listBankTransfers", "getBankTransfer"],
            "customers" => ["createCustomer", "listCustomers", "getCustomer"],
            "inboundDocuments" => ["acknowledgeInboundDocument", "listInboundDocuments"],
            "invoices" => ["createInvoice", "issueInvoice", "cancelInvoice", "listInvoices", "getInvoice", "issueCreditNote"],
            "paymentLinks" => ["createPaymentLink", "getPaymentLink", "listPaymentLinks", "updatePaymentLink", "disablePaymentLink", "enablePaymentLink"],
            "payments" => ["listPayments", "getPayment", "createPayment", "cancelPayment", "voidAuthorization", "requestCapture", "listUnappliedReceipts"],
            "refunds" => ["refundPayment", "getRefundable", "getRefund", "listRefunds"],
            "subvendors" => ["createSubvendor", "updateSubvendor", "getSubvendor", "listSubvendors", "submitSubvendor", "suspendSubvendor", "resumeSubvendor", "issueOnboardingLink"],
            "transactions" => ["listWalletTransactions"],
            "wallets" => ["listWallets", "getWallet", "sendPayment", "transferBetweenWallets"],
            "webhooks" => ["createSubscription", "rotateSecret", "updateEndpoint", "updateEventTypes", "disableSubscription", "enableSubscription", "revokeSubscription", "listSubscriptions", "getSubscription", "sendTestEvent", "redeliverDelivery", "listDeliveries", "getDelivery"],
        ];

        $methods = 0;
        foreach ($services as $property => $names) {
            foreach ($names as $name) {
                self::assertTrue(method_exists($client->{$property}, $name), "$property->$name()");
                $methods++;
            }
        }
        self::assertSame(56, $methods);
    }
}
