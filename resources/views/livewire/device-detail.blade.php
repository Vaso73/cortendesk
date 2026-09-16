<div wire:poll.15s>
    {{-- Header: who this is and how to reach it. --}}
    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <x-platform-icon :platform="$device->platform()" size="fs-32"/>
            <div class="min-width-0 me-auto">
                <h4 class="mb-0 text-truncate">{{ $device->alias ?: $device->hostname ?: $device->rustdesk_id }}</h4>
                <span class="text-muted">
                    {{ $device->rustdesk_id }}
                    @if ($device->alias && $device->hostname) · {{ $device->hostname }} @endif
                </span>
            </div>
            @if ($device->trashed())
                <span class="badge bg-warning-subtle text-warning">{{ __('devices.detail.in_recycle_bin') }}</span>
            @elseif ($device->isOnline())
                <span class="badge bg-success-subtle text-success"><i class="rd-dot"></i>{{ __('devices.common.online') }}</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary"><i class="rd-dot"></i>{{ __('devices.common.offline') }}</span>
            @endif
            @unless ($device->trashed())
                <div class="d-flex gap-2">
                    <a href="rustdesk://{{ $device->rustdesk_id }}" class="btn btn-sm btn-outline-light"
                       title="{{ __('devices.list.connect_rustdesk') }}"><i class="ri-links-line me-1"></i>RustDesk</a>
                    @if (config('cortendesk.native_webclient'))
                        <a href="{{ route('webclient') }}?id={{ $device->rustdesk_id }}"
                           target="cortendesk-webclient" rel="noopener" class="btn btn-sm btn-primary">
                            <i class="ri-remote-control-line me-1"></i>{{ __('devices.list.connect') }}</a>
                    @endif
                </div>
            @endunless
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            {{-- Facts. Everything the client reports, plus console assignment. --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('devices.detail.device') }}</h5></div>
                <div class="card-body pt-0">
                    <dl class="rd-deflist">
                        <div class="rd-def"><dt>OS</dt><dd class="text-end" title="{{ $device->os }}">{{ $device->osDescription() ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>CPU</dt><dd class="text-end">{{ $device->cpu ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.common.memory') }}</dt><dd>{{ $device->memory ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.detail.client_version') }}</dt><dd>{{ $device->version ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.detail.client_user') }}</dt><dd>{{ $device->username ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.detail.last_ip') }}</dt><dd class="rd-mono">{{ $device->last_online_ip ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.detail.registered_from') }}</dt><dd class="rd-mono" title="{{ __('devices.detail.registered_from_help') }}">{{ $device->registered_ip ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>UUID</dt><dd class="rd-mono text-truncate" style="max-width: 220px" title="{{ $device->uuid }}">{{ $device->uuid ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.common.first_seen') }}</dt><dd>{{ $device->created_at?->diffForHumans() ?? '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.common.last_seen') }}</dt><dd>{{ $device->last_online_at?->diffForHumans() ?? __('devices.common.never') }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.common.group') }}</dt><dd>{{ $device->group?->name ?: '—' }}</dd></div>
                        <div class="rd-def"><dt>{{ __('devices.common.owner') }}</dt>
                            <dd>
                                @if ($device->user)
                                    <span class="badge bg-info-subtle text-info">{{ $device->user->username }}</span>
                                @else — @endif
                            </dd>
                        </div>
                        <div class="rd-def"><dt>{{ __('devices.common.strategy') }}</dt><dd>{{ $device->resolvedStrategy?->name ?: '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            {{-- Note: the one editable field here. Everything else is either
                 client-reported or has its own screen. --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('devices.detail.note') }}</h5>
                    @if ($canEdit && ! $editingNote)
                        <a href="javascript:void(0);" class="fs-13 text-muted" wire:click="editNote">
                            <i class="ri-pencil-line me-1"></i>{{ __('devices.common.edit') }}</a>
                    @endif
                </div>
                <div class="card-body pt-0">
                    @if ($editingNote)
                        <textarea class="form-control @error('note') is-invalid @enderror" rows="3"
                                  wire:model="note" maxlength="1000"></textarea>
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-primary" wire:click="saveNote">{{ __('devices.common.save') }}</button>
                            <button type="button" class="btn btn-sm btn-outline-light" wire:click="cancelNote">{{ __('devices.common.cancel') }}</button>
                        </div>
                    @else
                        <p class="mb-0 {{ $device->note ? '' : 'text-muted' }}">{{ $device->note ?: __('devices.detail.no_note') }}</p>
                    @endif
                </div>
            </div>

            {{-- Membership: which books hand this device out. --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ __('devices.detail.address_books') }}</h5></div>
                <div class="card-body pt-0">
                    @forelse ($books as $book)
                        <span class="badge bg-secondary-subtle text-secondary me-1 mb-1">
                            <i class="ri-contacts-book-2-line me-1"></i>{{ $book->is_personal ? ($book->owner?->username ? __('devices.detail.owners_personal', ['owner' => $book->owner->username]) : __('devices.common.personal_book')) : $book->name }}
                        </span>
                    @empty
                        <span class="text-muted">{{ __('devices.detail.no_visible_book') }}</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @if ($canAudit)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('devices.detail.recent_connections') }}</h5>
                        <a href="{{ route('logs.connections') }}?search={{ $device->rustdesk_id }}" class="fs-13 text-muted">{{ __('devices.common.all') }}</a>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($connections as $c)
                            <div class="rd-def">
                                <dt class="fw-normal">
                                    {{ $c->from_name ?: $c->from_peer ?: __('devices.common.unknown') }}
                                    <span class="text-muted">· {{ $c->ip }}</span>
                                </dt>
                                <dd class="text-end text-muted mb-0">
                                    {{ $c->created_at->diffForHumans() }}
                                    @if ($c->closed_at) · {{ $c->created_at->diffAsCarbonInterval($c->closed_at)->cascade()->forHumans(short: true) }} @endif
                                </dd>
                            </div>
                        @empty
                            <span class="text-muted">{{ __('devices.detail.no_connections') }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('devices.detail.recent_transfers') }}</h5>
                        <a href="{{ route('logs.file-transfers') }}?search={{ $device->rustdesk_id }}" class="fs-13 text-muted">{{ __('devices.common.all') }}</a>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($transfers as $t)
                            <div class="rd-def">
                                <dt class="fw-normal text-truncate" style="max-width: 70%" title="{{ $t->path }}">
                                    <i class="ri-arrow-{{ $t->direction ? 'up' : 'down' }}-line me-1"></i>{{ $t->path ?: '—' }}
                                </dt>
                                <dd class="text-end text-muted mb-0">{{ $t->created_at->diffForHumans() }}</dd>
                            </div>
                        @empty
                            <span class="text-muted">{{ __('devices.detail.no_transfers') }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __('devices.detail.alarms') }}</h5>
                        <a href="{{ route('logs.alarms') }}?search={{ $device->rustdesk_id }}" class="fs-13 text-muted">{{ __('devices.common.all') }}</a>
                    </div>
                    <div class="card-body pt-0">
                        @forelse ($alarms as $a)
                            <div class="rd-def">
                                <dt class="fw-normal">
                                    <span class="badge bg-{{ $a->typeSeverity() }}-subtle text-{{ $a->typeSeverity() }}">
                                        @php $alarmLabelKey = 'devices.dashboard.alarm_types.'.$a->typ; @endphp
                                        {{ \Illuminate\Support\Facades\Lang::has($alarmLabelKey) ? __($alarmLabelKey) : __('devices.dashboard.unknown_alarm_type', ['type' => $a->typ]) }}
                                    </span>
                                </dt>
                                <dd class="text-end text-muted mb-0">{{ $a->created_at->diffForHumans() }}</dd>
                            </div>
                        @empty
                            <span class="text-muted">{{ __('devices.detail.no_alarms') }}</span>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-muted">
                        {{ __('devices.detail.audit_required') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
