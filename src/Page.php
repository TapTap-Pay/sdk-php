<?php

declare(strict_types=1);

namespace TapTap\Pay;

/**
 * One chunk of items returned by a List endpoint plus the pagination
 * meta. Produced by {@see Pagination::pages()}.
 */
final class Page
{
    /**
     * @param array       $items List of message objects on this page.
     * @param object|null $meta  \Common\V1\PaginatedResponseMeta or null when
     *                           the endpoint omitted meta (terminal page or
     *                           empty result).
     */
    public function __construct(
        public readonly array $items,
        public readonly ?object $meta,
    ) {
    }
}
