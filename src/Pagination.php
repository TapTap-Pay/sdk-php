<?php

declare(strict_types=1);

namespace TapTap\Pay;

/**
 * Page iteration helpers for List endpoints. The TapTap-Pay API
 * returns 1-indexed pages with a {@see \Common\V1\PaginatedResponseMeta}
 * carrying `total_pages`; these helpers walk them lazily.
 *
 * The fetch callback returns an array `[items, meta]`:
 *
 *   - items: list<T> for the current page
 *   - meta:  \Common\V1\PaginatedResponseMeta|null
 *
 * Example:
 *
 *     foreach (Pagination::pages(fn ($page) => (function () use ($client, $page) {
 *         $resp = $client->paymentLinks->listPaymentLinks(
 *             (new ListPaymentLinksRequest())
 *                 ->setPagination((new PaginationRequestData())->setPage($page)->setPageSize(100)),
 *         );
 *         return [iterator_to_array($resp->getLinks()), $resp->getMeta()];
 *     })()) as $page) {
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
     * @param callable(int): array{0: array, 1: ?object} $fetch
     * @return \Generator<int, Page>
     */
    public static function pages(callable $fetch): \Generator
    {
        $page = 1;
        while (true) {
            [$items, $meta] = $fetch($page);
            yield new Page($items, $meta);
            if ($meta === null) {
                return;
            }
            $totalPages = $meta->getTotalPages();
            if ($page >= $totalPages) {
                return;
            }
            $page++;
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
