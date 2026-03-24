<?php

namespace App\Mail;

use App\Models\Page;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sprint 12.3 — Page Updated notification email.
 *
 * Sent to every watcher of a page who has opted in to email notifications
 * whenever the page is saved.  Implements ShouldQueue so delivery is
 * handled by the queue worker (or inline if QUEUE_CONNECTION=sync).
 */
class PageUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Page      $page,
        public readonly Workspace $workspace,
        public readonly User      $editor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Codex] "' . $this->page->title . '" was updated by ' . $this->editor->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.page-updated',
        );
    }
}
