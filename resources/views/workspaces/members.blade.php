<x-app-layout>
    <x-slot name="header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.show', $workspace) }}">{{ $workspace->name }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('workspaces.edit', $workspace) }}">Settings</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Members</li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-4">
        <div class="container" style="max-width: 48rem;">

            {{-- Flash messages --}}
            @foreach(['member-added' => ['success', 'Member added.'], 'member-removed' => ['info', 'Member removed.'], 'member-updated' => ['success', 'Role updated.']] as $key => [$type, $msg])
                @if(session('status') === $key)
                    <div class="alert alert-{{ $type }} alert-dismissible mb-4 py-2" role="alert">
                        <i class="fas fa-check-circle me-1"></i> {{ $msg }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            @endforeach

            {{-- Current members --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header p-4 d-flex align-items-center justify-content-between">
                    <h1 class="h5 mb-0">
                        <i class="fas fa-users me-2 text-body-secondary"></i>Members
                        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1 fw-normal">
                            {{ $members->count() + 1 }}
                        </span>
                    </h1>
                    <a href="{{ route('workspaces.edit', $workspace) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-cog me-1"></i> Settings
                    </a>
                </div>

                <div class="list-group list-group-flush">
                    {{-- Owner row (non-editable) --}}
                    <div class="list-group-item px-4 py-3 d-flex align-items-center gap-3">
                        <span class="avatar-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:36px;height:36px;font-size:0.8rem;">
                            {{ strtoupper(substr($owner->name, 0, 2)) }}
                        </span>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-medium small text-truncate">{{ $owner->name }}</div>
                            <div class="text-body-secondary" style="font-size:0.75rem;">{{ $owner->email }}</div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary-emphasis flex-shrink-0">Owner</span>
                    </div>

                    {{-- Editable member rows --}}
                    @forelse($members as $member)
                        <div class="list-group-item px-4 py-3 d-flex align-items-center gap-3">
                            <span class="avatar-circle bg-secondary-subtle text-secondary-emphasis fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                  style="width:36px;height:36px;font-size:0.8rem;">
                                {{ strtoupper(substr($member->name, 0, 2)) }}
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-medium small text-truncate">{{ $member->name }}</div>
                                <div class="text-body-secondary" style="font-size:0.75rem;">{{ $member->email }}</div>
                            </div>

                            {{-- Role change form --}}
                            <form method="POST"
                                  action="{{ route('workspaces.members.update', [$workspace, $member]) }}"
                                  class="d-flex align-items-center gap-2 flex-shrink-0">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="form-select form-select-sm" style="width:auto;"
                                        onchange="this.form.submit()">
                                    @foreach(['member' => 'Member', 'editor' => 'Editor', 'admin' => 'Admin'] as $val => $label)
                                        <option value="{{ $val }}" {{ $member->pivot->role === $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>

                            {{-- Remove member --}}
                            <form method="POST"
                                  action="{{ route('workspaces.members.destroy', [$workspace, $member]) }}"
                                  class="flex-shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                        title="Remove member"
                                        onclick="return confirm('Remove ' + @js($member->name) + ' from this workspace?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="list-group-item px-4 py-3 text-body-secondary small">
                            No additional members yet.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Add member form --}}
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h2 class="h6 fw-semibold mb-0">
                        <i class="fas fa-user-plus me-2 text-body-secondary"></i>Add Member
                    </h2>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 mb-3 small">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('workspaces.members.store', $workspace) }}">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6">
                                <label for="member-email" class="form-label small fw-medium">Email address</label>
                                <div class="position-relative">
                                    <input type="email" id="member-email" name="email"
                                           value="{{ old('email') }}"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="teammate@example.com"
                                           autocomplete="off" />
                                    {{-- Typeahead dropdown --}}
                                    <div id="member-suggestions"
                                         class="dropdown-menu w-100 shadow-sm"
                                         style="display:none; position:absolute; top:100%; z-index:1000;"></div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <label for="member-role" class="form-label small fw-medium">Role</label>
                                <select id="member-role" name="role" class="form-select">
                                    <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>Member</option>
                                    <option value="editor" {{ old('role') === 'editor' ? 'selected' : '' }}>Editor</option>
                                    <option value="admin"  {{ old('role') === 'admin'  ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        {{-- Sprint 14.3: Invite by email --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h2 class="h6 fw-semibold mb-0">
                    <i class="fas fa-envelope-open-text me-2 text-body-secondary"></i>Invite via Email
                </h2>
            </div>
            <div class="card-body p-4">
                @if(session('status') === 'invitation-sent')
                    <div class="alert alert-success py-2 mb-3 small">
                        <i class="fas fa-check-circle me-1"></i> Invitation sent.
                    </div>
                @endif
                @if(session('status') === 'invitation-link-created')
                    <div class="alert alert-success py-2 mb-3 small">
                        <i class="fas fa-link me-1"></i>
                        Shareable invite link:
                        <code class="ms-1 user-select-all">{{ session('invitation_link') }}</code>
                    </div>
                @endif
                @if(session('status') === 'invitation-revoked')
                    <div class="alert alert-info py-2 mb-3 small">
                        <i class="fas fa-times me-1"></i> Invitation revoked.
                    </div>
                @endif
                @if($errors->has('email'))
                    <div class="alert alert-danger py-2 mb-3 small">{{ $errors->first('email') }}</div>
                @endif

                <form method="POST" action="{{ route('workspaces.invitations.store', $workspace) }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6">
                            <label for="invite-email" class="form-label small fw-medium">
                                Email address
                                <span class="fw-normal text-body-secondary">(leave blank for a shareable link)</span>
                            </label>
                            <input type="email" id="invite-email" name="email"
                                   class="form-control"
                                   placeholder="colleague@example.com"
                                   value="{{ old('email') }}" />
                        </div>
                        <div class="col-sm-3">
                            <label for="invite-role" class="form-label small fw-medium">Role</label>
                            <select id="invite-role" name="role" class="form-select">
                                <option value="member">Member</option>
                                <option value="editor">Editor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i>
                                <span id="invite-btn-text">Send Invite</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pending invitations list --}}
        @if($pendingInvitations->isNotEmpty())
        <div class="card shadow-sm mt-4">
            <div class="card-header py-3">
                <h2 class="h6 fw-semibold mb-0">
                    <i class="fas fa-clock me-2 text-body-secondary"></i>Pending Invitations
                    <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal ms-1">
                        {{ $pendingInvitations->count() }}
                    </span>
                </h2>
            </div>
            <div class="list-group list-group-flush">
                @foreach($pendingInvitations as $invite)
                <div class="list-group-item d-flex align-items-center gap-3 py-2 px-4">
                    <i class="fas fa-envelope text-body-secondary small flex-shrink-0"></i>
                    <div class="flex-grow-1 overflow-hidden">
                        @if($invite->email)
                            <div class="small fw-medium text-truncate">{{ $invite->email }}</div>
                        @else
                            <div class="small fw-medium text-body-secondary fst-italic">Shareable link</div>
                        @endif
                        <div class="text-body-secondary" style="font-size:.72rem;">
                            Invited by {{ $invite->invitedBy->name }} &middot;
                            expires {{ $invite->expires_at->diffForHumans() }}
                        </div>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal"
                          style="font-size:.65rem;">{{ ucfirst($invite->role) }}</span>
                    @if(!$invite->email)
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary copy-link-btn"
                                data-link="{{ route('invitations.show', $invite->token) }}"
                                title="Copy invite link">
                            <i class="fas fa-copy"></i>
                        </button>
                    @endif
                    <form method="POST"
                          action="{{ route('workspaces.invitations.destroy', [$workspace, $invite]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                title="Revoke invitation"
                                onclick="return confirm('Revoke this invitation?')">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        </div>
    </div>

    @push('scripts')
    <script>
        // Simple typeahead: query the member search endpoint as the user types
        const emailInput   = document.getElementById('member-email');
        const suggestions  = document.getElementById('member-suggestions');
        const searchUrl    = '{{ route('workspaces.members.search', $workspace) }}';
        let debounce;

        emailInput.addEventListener('input', () => {
            clearTimeout(debounce);
            const q = emailInput.value.trim();
            if (q.length < 2) { suggestions.style.display = 'none'; return; }
            debounce = setTimeout(async () => {
                try {
                    const res  = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    suggestions.innerHTML = '';
                    if (!data.length) { suggestions.style.display = 'none'; return; }
                    data.forEach(user => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'dropdown-item d-flex flex-column py-2';
                        item.innerHTML = `<span class="fw-medium small">${user.name}</span><span class="text-body-secondary" style="font-size:.75rem;">${user.email}</span>`;
                        item.addEventListener('click', () => {
                            emailInput.value = user.email;
                            suggestions.style.display = 'none';
                        });
                        suggestions.appendChild(item);
                    });
                    suggestions.style.display = 'block';
                } catch (e) { suggestions.style.display = 'none'; }
            }, 250);
        });

        document.addEventListener('click', e => {
            if (!emailInput.contains(e.target)) suggestions.style.display = 'none';
        });

        // Update invite button label based on whether email is filled
        const inviteEmail = document.getElementById('invite-email');
        const inviteBtnText = document.getElementById('invite-btn-text');
        if (inviteEmail && inviteBtnText) {
            inviteEmail.addEventListener('input', () => {
                inviteBtnText.textContent = inviteEmail.value.trim() ? 'Send Invite' : 'Create Link';
            });
        }

        // Copy shareable link to clipboard
        document.querySelectorAll('.copy-link-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(btn.dataset.link);
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 2000);
                } catch (_) {
                    prompt('Copy this invite link:', btn.dataset.link);
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
