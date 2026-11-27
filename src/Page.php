<?php

declare(strict_types=1);

namespace TapTap\Pay;

/**
 * One chunk of items returned by a List endpoint plus the cursor the
 * server returned for the page after it. Produced by
 * {@see Pagination::pages()}.
 */
final class Page
{
    /**
     * @param array  $items         List of message objects on this page.
     * @param string $nextPageToken Cursor for the following page; empty
     *                              on the last page.
     */
    public function __construct(
        public readonly array $items,
        public readonly string $nextPageToken,
    ) {
    }
}
