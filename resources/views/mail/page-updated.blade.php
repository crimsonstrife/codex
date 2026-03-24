<x-mail::message>
# {{ $page->title }} was updated

**{{ $editor->name }}** made changes to the page **"{{ $page->title }}"** in the **{{ $workspace->name }}** workspace.

<x-mail::panel>
**Workspace:** {{ $workspace->name }}
**Page:** {{ $page->title }}
**Updated by:** {{ $editor->name }}
**Status:** {{ ucfirst($page->status) }}
</x-mail::panel>

<x-mail::button :url="route('workspaces.pages.show', [$workspace, $page])" color="primary">
View Page
</x-mail::button>

---

You're receiving this email because you're watching this page in Codex.
To stop receiving these emails, turn off **Email Notifications** in your
[profile preferences]({{ route('profile.show') }}).

Thanks,
{{ config('app.name') }}
</x-mail::message>
