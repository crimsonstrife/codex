<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Collection;

final class PageNavigationResolver
{
    /**
     * Resolve child, previous, and next pages from an already-ordered workspace page collection.
     *
     * @return array{
     *     childPages: Collection<int, Page>,
     *     previousPage: ?Page,
     *     nextPage: ?Page
     * }
     */
    public function resolve(Collection $orderedPages, Page $page): array
    {
        $currentIndex = $orderedPages->search(
            fn (Page $candidate): bool => $candidate->is($page)
        );

        return [
            'childPages' => $orderedPages
                ->where('parent_id', $page->id)
                ->values(),
            'previousPage' => $currentIndex !== false && $currentIndex > 0
                ? $orderedPages->get($currentIndex - 1)
                : null,
            'nextPage' => $currentIndex !== false
                ? $orderedPages->get($currentIndex + 1)
                : null,
        ];
    }
}
