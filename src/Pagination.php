<?php

declare(strict_types=1);

namespace TapTap\Pay;

/**
 * Page iteration helpers for List endpoints. The TapTap-Pay API pages
 * with an opaque cursor: a response carries the cursor of the next
 * page, empty when there are no more. These helpers walk the pages
 * lazily.
 *
 * The fetch callback receives the cursor of the page to fetch (empty
 * for the first page) and returns an array `[items, nextPageToken]`:
 *
 *   - items:         list<T> for the current page
 *   - nextPageToken: string, empty on the last page
 *
 * The cursor is spelled differently across the List endpoints, which
 * is why the callback does the mapping: most requests take
 * setPageToken() and answer getNextPageToken(); listInvoices and
 * listInboundDocuments take setCursor() and answer getNextCursor();
 * listSubvendors takes setPage(PageRequest) and answers
 * getPage()?->getNextPageToken().
 *
 * Example:
 *
 *     $fetch = function (string $pageToken) use ($client, $vendorId) {
 *         $resp = $client->paymentLinks->listPaymentLinks(
 *             (new ListPaymentLinksRequest())
 *                 ->setVendorId($vendorId)
 *                 ->setPageSize(100)
 *                 ->setPageToken($pageToken),
 *         );
 *         return [iterator_to_array($resp->getPaymentLinks()), $resp->getNextPageToken()];
 *     };
 *     foreach (Pagination::pages($fetch) as $page) {
 *         foreach ($page->items as $link) {
 *             echo $link->getId(), "\n";
 *         }
 *     }
 */
final class Pagination
{
    private function __construct()
    {
    }

    /**
     * Walks every page of a List endpoint, yielding one {@see Page}
     * at a time.
     *
     * @param callable(string): array{0: array, 1: ?string} $fetch
     * @return \Generator<int, Page>
     */
    public static function pages(callable $fetch): \Generator
    {
        $pageToken = "";
        while (true) {
            [$items, $next] = $fetch($pageToken);
            $next = (string) $next;
            yield new Page($items, $next);
            if ($next === "") {
                return;
            }
            $pageToken = $next;
        }
    }

    /**
     * Flattens a paged iterator to one item at a time.
     *
     * @param iterable<Page> $pages
     * @return \Generator
     */
    public static function items(iterable $pages): \Generator
    {
        foreach ($pages as $page) {
            foreach ($page->items as $item) {
                yield $item;
            }
        }
    }
}
