<?php

namespace App\Services;

use App\Models\Diagram;
use Illuminate\Support\Str;

class DiagramDeletionGuard
{
    public function embeddedPageCount(Diagram $diagram): int
    {
        return $diagram->pageEmbeds()->count();
    }

    public function canDelete(Diagram $diagram): bool
    {
        return $this->embeddedPageCount($diagram) === 0;
    }

    public function blockingMessage(Diagram $diagram): string
    {
        $embedCount = $this->embeddedPageCount($diagram);

        return 'Remove it from '.$embedCount.' '.Str::plural('page', $embedCount).' before deleting it.';
    }
}
