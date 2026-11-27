<?php

declare(strict_types=1);

namespace TapTap\Pay\Tests;

use PHPUnit\Framework\TestCase;
use Taptap\Common\V1\PageResponse;
use Taptap\Payment\Programmatic\V1\ListPaymentLinksResponse;
use Taptap\Payment\Programmatic\V1\PaymentLink;
use TapTap\Pay\Pagination;

final class PaginationTest extends TestCase
{
    public function testPagesFollowsTheNextPageTokenUntilItIsEmpty(): void
    {
        $responses = [
            "" => (new ListPaymentLinksResponse())
                ->setPaymentLinks([(new PaymentLink())->setId("plink_1"), (new PaymentLink())->setId("plink_2")])
                ->setNextPageToken("page-2"),
            "page-2" => (new ListPaymentLinksResponse())
                ->setPaymentLinks([(new PaymentLink())->setId("plink_3")]),
        ];
        $asked = [];
        $fetch = function (string $pageToken) use ($responses, &$asked): array {
            $asked[] = $pageToken;
            $resp = $responses[$pageToken];
            return [iterator_to_array($resp->getPaymentLinks()), $resp->getNextPageToken()];
        };

        $ids = [];
        foreach (Pagination::items(Pagination::pages($fetch)) as $link) {
            $ids[] = $link->getId();
        }

        self::assertSame(["plink_1", "plink_2", "plink_3"], $ids);
        self::assertSame(["", "page-2"], $asked);
    }

    public function testPagesCarryTheCursorAndStopOnAMissingOne(): void
    {
        // listSubvendors answers a PageResponse; an absent one reads as null.
        $absent = null;
        $fetch = fn (string $pageToken): array => $pageToken === ""
            ? [["a"], (new PageResponse())->setNextPageToken("next")->getNextPageToken()]
            : [["b"], $absent?->getNextPageToken()];

        $pages = iterator_to_array(Pagination::pages($fetch), false);

        self::assertCount(2, $pages);
        self::assertSame("next", $pages[0]->nextPageToken);
        self::assertSame("", $pages[1]->nextPageToken);
        self::assertSame(["b"], $pages[1]->items);
    }
}
