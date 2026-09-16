<div wire:poll.15s>

    {{-- Summary chips. Buttons, not badges (issue #26): each one filters the
         list underneath, and the active one is marked. Pending only appears
         when something is actually waiting. --}}
    <div class="rd-chiprow">
        <button type="button" class="rd-chip rd-chip-btn rd-tone-blue @if(! $trashed && ! $pendingTab && $status === 'all') rd-chip-active @endif"
                wire:click="filterByChip('all')" title="{{ __('devices.list.show_every_device') }}">
            <i class="ri-computer-line rd-chip-icon"></i>
            <span class="rd-chip-value">{{ $totalCount }}</span>
            <span class="rd-chip-label">{{ trans_choice('devices.count.device_label', \App\Livewire\DeviceList::pluralSelector($totalCount)) }}</span>
        </button>
        <button type="button" class="rd-chip rd-chip-btn rd-tone-green @if(! $trashed && ! $pendingTab && $status === 'online') rd-chip-active @endif"
                wire:click="filterByChip('online')" title="{{ __('devices.list.only_online') }}">
            <span class="rd-chip-dot"></span>
            <span class="rd-chip-value">{{ $onlineCount }}</span>
            <span class="rd-chip-label">{{ __('devices.common.online') }}</span>
        </button>
        <button type="button" class="rd-chip rd-chip-btn rd-tone-muted @if(! $trashed && ! $pendingTab && $status === 'offline') rd-chip-active @endif"
                wire:click="filterByChip('offline')" title="{{ __('devices.list.only_offline') }}">
            <span class="rd-chip-dot"></span>
            <span class="rd-chip-value">{{ $totalCount - $onlineCount }}</span>
            <span class="rd-chip-label">{{ __('devices.common.offline') }}</span>
        </button>
        @if ($pendingCount > 0)
            <button type="button" class="rd-chip rd-chip-btn rd-tone-amber @if($pendingTab) rd-chip-active @endif"
                    wire:click="openPending" title="{{ __('devices.list.waiting_for_approval') }}">
                <i class="ri-time-line rd-chip-icon"></i>
                <span class="rd-chip-value">{{ $pendingCount }}</span>
                <span class="rd-chip-label">{{ __('devices.common.pending') }}</span>
            </button>
        @endif
    </div>

    <div class="card">

        {{-- Toolbar --}}
        <div class="rd-toolbar">
            <div class="rd-toolbar-search">
                <div class="input-group">
                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                    <input type="search" class="form-control" placeholder="{{ __('devices.list.search') }}"
                           wire:model.live.debounce.300ms="search">
                </div>
            </div>
            <select class="form-select rd-toolbar-filter" wire:model.live="status" @disabled($trashed)>
                <option value="all">{{ __('devices.list.all_statuses') }}</option>
                <option value="online">{{ __('devices.common.online') }}</option>
                <option value="offline">{{ __('devices.common.offline') }}</option>
            </select>
            <select class="form-select rd-toolbar-filter" wire:model.live="group">
                <option value="0">{{ __('devices.list.all_groups') }}</option>
                @foreach ($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
            @if (auth()->user()?->is_admin)
                <select class="form-select rd-toolbar-filter" wire:model.live="owner">
                    <option value="0">{{ __('devices.list.all_owners') }}</option>
                    <option value="-1">{{ __('devices.common.unassigned') }}</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->username }}</option>
                    @endforeach
                </select>
            @endif
            {{--
                Sorting on a phone: the card list has no headings to click, so
                the picker and the reverse arrow stand in for them. Options come
                from the component's own allowlist so the two cannot drift.
            --}}
            <div class="rd-sort-mobile d-md-none">
                <select class="form-select rd-toolbar-filter" aria-label="{{ __('devices.list.sort_by') }}"
                        wire:change="selectSort($event.target.value)">
                    @foreach (\App\Livewire\DeviceList::SORTABLE as $key => $unused)
                        @continue($key === 'owner' && ! auth()->user()?->is_admin)
                        <option value="{{ $key }}" @selected($sortField === $key)>
                            {{ __('devices.list.sort_option', ['column' => $key === 'id' ? __('devices.common.id') : (\App\Livewire\DeviceList::columnLabels()[$key] ?? __('devices.common.status'))]) }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-light" wire:click="sortBy('{{ $sortField }}')"
                        aria-label="{{ __('devices.list.reverse_sort') }}" title="{{ __('devices.list.reverse_sort') }}">
                    <i class="{{ $sortDirection === 'asc' ? 'ri-sort-asc' : 'ri-sort-desc' }}"></i>
                </button>
            </div>
            <div class="rd-toolbar-actions">
                <button type="button" class="btn btn-outline-light" wire:click="resetFilters">{{ __('devices.common.reset') }}</button>
                @if ($trashed)
                    <button type="button" class="btn btn-light" wire:click="$set('trashed', false)">
                        <i class="ri-arrow-left-line"></i>{{ __('devices.pages.back_to_devices') }}
                    </button>
                @elseif (auth()->user()?->consoleAllows('device', 'rw'))
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="ri-add-line"></i>{{ __('devices.list.add_device') }}
                    </button>
                @endif
            </div>
        </div>

        @unless ($trashed)
            <div class="rd-toolbar">
                <a href="javascript:void(0);" class="fs-13 text-muted d-none d-md-inline"
                   wire:click="$toggle('columnsOpen')">
                    <i class="ri-layout-column-line me-1"></i>{{ __('devices.list.columns') }}
                </a>
                <a href="javascript:void(0);" class="fs-13 text-muted" wire:click="exportCsv"
                   title="{{ __('devices.list.download_csv') }}">
                    <i class="ri-download-2-line me-1"></i>{{ __('devices.list.export_csv') }}
                </a>
                @if ($pendingCount > 0)
                    <a href="javascript:void(0);" class="fs-13 text-warning fw-semibold" wire:click="$set('pendingTab', true)">
                        <i class="ri-time-line me-1"></i>{{ __('devices.list.pending_approval') }}
                        <span class="badge bg-warning-subtle text-warning ms-1">{{ $pendingCount }}</span>
                    </a>
                @endif
                <a href="javascript:void(0);" class="fs-13 text-muted ms-auto" wire:click="$set('trashed', true)">
                    <i class="ri-delete-bin-line me-1"></i>{{ __('devices.list.recycle_bin_count', ['count' => $trashedCount]) }}
                </a>
            </div>

            {{-- Column picker (issue #16). A plain inline panel, not a Bootstrap
                 dropdown: Livewire re-renders on every checkbox and a JS-owned
                 dropdown would snap shut after each click. Desktop-only, like
                 the table it configures — the mobile cards have a fixed layout. --}}
            @if ($columnsOpen)
                <div class="rd-toolbar d-none d-md-flex">
                    <div class="rd-inset w-100">
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            @foreach (\App\Livewire\DeviceList::columnLabels() as $key => $label)
                                @continue ($key === 'owner' && ! auth()->user()?->is_admin)
                                <div class="form-check form-check-inline m-0">
                                    <input type="checkbox" class="form-check-input" id="col-{{ $key }}"
                                           value="{{ $key }}" wire:model.live="columns">
                                    <label class="form-check-label fs-13" for="col-{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                            <div class="ms-auto d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-light" wire:click="resetColumns">{{ __('devices.common.defaults') }}</button>
                                <button type="button" class="btn btn-sm btn-light" wire:click="$set('columnsOpen', false)">{{ __('devices.common.done') }}</button>
                            </div>
                        </div>
                        <div class="form-text">{{ __('devices.list.selection_saved') }}</div>
                    </div>
                </div>
            @endif
        @else
            <div class="rd-toolbar">
                <div class="alert alert-warning py-2 mb-0 w-100">
                    <i class="ri-delete-bin-line me-1"></i>{{ __('devices.list.recycle_notice') }}
                </div>
            </div>
        @endunless

        {{-- Bulk actions bar (issues #15, #47) — appears when rows are selected,
             on phones too now that the cards carry checkboxes. --}}
        @if (! $trashed && ($selected !== [] || $bulkResult !== ''))
            <div class="rd-toolbar d-flex flex-wrap gap-2 align-items-center">
                @if ($selected !== [])
                    <span class="fs-13 fw-semibold">{{ trans_choice('devices.count.selected', \App\Livewire\DeviceList::pluralSelector(count($selected)), ['count' => count($selected)]) }}</span>
                    @if (auth()->user()?->consoleAllows('address_book', 'rw'))
                        <button type="button" class="btn btn-sm btn-light" wire:click="openAbPicker">
                            <i class="ri-contacts-book-2-line me-1"></i>{{ __('devices.list.add_to_address_book') }}
                        </button>
                    @endif
                    @if (auth()->user()?->consoleAllows('device', 'rw'))
                        <button type="button" class="btn btn-sm btn-light" wire:click="openGroupPicker">
                            <i class="ri-folder-transfer-line me-1"></i>{{ __('devices.list.move_to_group') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="bulkDelete"
                                wire:confirm="{{ __('devices.confirm.bulk_recycle', ['devices' => trans_choice('devices.count.device', \App\Livewire\DeviceList::pluralSelector(count($selected)), ['count' => count($selected)])]) }}">
                            <i class="ri-delete-bin-line me-1"></i>{{ __('devices.common.delete') }}
                        </button>
                    @endif
                    <a href="javascript:void(0);" class="fs-13 text-muted" wire:click="clearSelection">{{ __('devices.common.clear') }}</a>
                @endif
                @if ($bulkResult !== '')
                    <span class="fs-13 text-success"><i class="ri-check-line me-1"></i>{{ $bulkResult }}</span>
                @endif
            </div>
        @endif

        {{-- Desktop table (md and up) --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover table-centered mb-0">
                <thead>
                <tr>
                    <th class="rd-selcol">
                        @unless ($trashed)
                            @php $pageIds = $devices->pluck('id')->map(fn ($i) => (string) $i); @endphp
                            @if ($pageIds->isNotEmpty() && $pageIds->diff($selected)->isEmpty())
                                <input type="checkbox" class="form-check-input" checked
                                       wire:click="clearSelection" title="{{ __('devices.list.clear_selection') }}">
                            @else
                                <input type="checkbox" class="form-check-input"
                                       wire:click="selectPage" title="{{ __('devices.list.select_page') }}">
                            @endif
                        @endunless
                    </th>
                    <x-sortable-th field="id" :sort="$sortField" :dir="$sortDirection">ID</x-sortable-th>
                    @foreach (array_intersect_key(\App\Livewire\DeviceList::columnLabels(), array_flip(['device', 'alias', 'group', 'owner', 'version'])) as $key => $label)
                        @if ($cols[$key])
                            <x-sortable-th :field="$key" :sort="$sortField" :dir="$sortDirection">{{ $label }}</x-sortable-th>
                        @endif
                    @endforeach
                    @if ($cols['os'])<x-sortable-th field="os" :sort="$sortField" :dir="$sortDirection">OS</x-sortable-th>@endif
                    @if ($cols['username'])<th>{{ __('devices.common.user') }}</th>@endif
                    @if ($cols['ip'])<th>IP</th>@endif
                    @if ($cols['cpu'])<th>CPU</th>@endif
                    @if ($cols['memory'])<th>{{ __('devices.common.memory') }}</th>@endif
                    @if ($cols['uuid'])<th>UUID</th>@endif
                    @foreach (array_intersect_key(\App\Livewire\DeviceList::columnLabels(), array_flip(['first_seen', 'last_seen'])) as $key => $label)
                        @if ($cols[$key])
                            <x-sortable-th :field="$key" :sort="$sortField" :dir="$sortDirection">{{ $label }}</x-sortable-th>
                        @endif
                    @endforeach
                    <x-sortable-th field="status" :sort="$sortField" :dir="$sortDirection">{{ __('devices.common.status') }}</x-sortable-th>
                    <th class="text-end">{{ __('devices.common.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($devices as $device)
                    <tr wire:key="d{{ $device->id }}">
                        <td class="rd-selcol">
                            @unless ($trashed)
                                <input type="checkbox" class="form-check-input" value="{{ $device->id }}"
                                       wire:model.live="selected">
                            @endunless
                        </td>
                        <td>
                            <x-platform-icon :platform="$device->platform()" class="me-1"/>
                            @if ($trashed)
                                <span class="fw-semibold">{{ $device->rustdesk_id }}</span>
                            @else
                                <a href="rustdesk://{{ $device->rustdesk_id }}" class="fw-semibold"
                                   title="{{ __('devices.list.connect_rustdesk') }}">{{ $device->rustdesk_id }}</a>
                            @endif
                        </td>
                        @if ($cols['device'])
                            <td>
                                @if ($trashed)
                                    <span class="rd-cell-title">{{ $device->hostname ?: '—' }}</span>
                                @else
                                    <a href="{{ route('devices.show', $device->id) }}" class="rd-cell-title rd-cell-link">{{ $device->hostname ?: '—' }}</a>
                                @endif
                                {{-- The subtitle only carries what has no column of its own on
                                     screen; enable OS or User and it leaves here (issue #33). --}}
                                @php
                                    $subParts = array_filter([
                                        $cols['os'] ? null : ($device->os ? \Illuminate\Support\Str::limit($device->osDescription(), 28) : null),
                                        $cols['username'] ? null : ($device->username ?: null),
                                    ]);
                                @endphp
                                @if ($subParts !== [])
                                    <span class="rd-cell-sub">{{ implode(' · ', $subParts) }}</span>
                                @endif
                            </td>
                        @endif
                        @if ($cols['alias'])<td>{{ $device->alias ?: '—' }}</td>@endif
                        @if ($cols['group'])<td>{{ $device->group?->name ?: '—' }}</td>@endif
                        @if ($cols['owner'])
                            <td>
                                @if ($device->user)
                                    <span class="badge bg-info-subtle text-info">{{ $device->user->username }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endif
                        @if ($cols['version'])
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $device->version ?: '?' }}</span></td>
                        @endif
                        @if ($cols['os'])<td class="rd-nowrap" title="{{ $device->os }}">{{ $device->os ? \Illuminate\Support\Str::limit($device->osDescription(), 34) : '—' }}</td>@endif
                        @if ($cols['username'])<td>{{ $device->username ?: '—' }}</td>@endif
                        @if ($cols['ip'])<td class="rd-mono fs-13 rd-nowrap">{{ $device->last_online_ip ?: '—' }}</td>@endif
                        @if ($cols['cpu'])
                            <td><span class="fs-13" title="{{ $device->cpu }}">{{ $device->cpu ? \Illuminate\Support\Str::limit($device->cpu, 24) : '—' }}</span></td>
                        @endif
                        @if ($cols['memory'])<td class="fs-13">{{ $device->memory ?: '—' }}</td>@endif
                        @if ($cols['uuid'])
                            <td><span class="rd-mono fs-13" title="{{ $device->uuid }}">{{ $device->uuid ? \Illuminate\Support\Str::limit($device->uuid, 12) : '—' }}</span></td>
                        @endif
                        @if ($cols['first_seen'])
                            <td><span title="{{ $device->created_at }}">{{ $device->created_at?->format('Y-m-d') ?? '—' }}</span></td>
                        @endif
                        @if ($cols['last_seen'])
                            <td>
                                <span title="{{ $device->last_online_at }}">
                                    {{ $device->last_online_at?->diffForHumans() ?? __('devices.common.never') }}
                                </span>
                            </td>
                        @endif
                        <td>
                            @if ($trashed)
                                <span class="badge bg-warning-subtle text-warning">{{ __('devices.common.deleted') }}</span>
                            @elseif ($device->isOnline())
                                <span class="badge bg-success-subtle text-success"><i class="rd-dot"></i>{{ __('devices.common.online') }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary"><i class="rd-dot"></i>{{ __('devices.common.offline') }}</span>
                            @endif
                        </td>
                        <td class="text-end rd-rowact">
                            @if ($trashed)
                                @if (auth()->user()?->consoleAllows('device', 'rw'))
                                    <a href="javascript:void(0);" class="text-success me-2" wire:click="restoreDevice({{ $device->id }})">{{ __('devices.common.restore') }}</a>
                                    <a href="javascript:void(0);" class="text-danger"
                                       wire:click="forceDeleteDevice({{ $device->id }})"
                                       wire:confirm="{{ __('devices.confirm.destroy', ['id' => $device->rustdesk_id]) }}">{{ __('devices.common.destroy') }}</a>
                                @endif
                            @else
                                {{-- Row actions are neutral; only the destructive one
                                     takes a colour. Two full-colour ordinary actions
                                     side by side read louder than Edit and make this
                                     the only cell in the console with four colours. --}}
                                @if (config('cortendesk.native_webclient'))
                                    <a href="{{ route('webclient') }}?id={{ $device->rustdesk_id }}"
                                       target="cortendesk-webclient" rel="noopener" class="rd-act me-2"
                                       title="{{ __('devices.list.connect_browser_native') }}"><i class="ri-remote-control-line me-1"></i>{{ __('devices.list.connect') }}</a>
                                @endif
                                @if (config('cortendesk.webclient_url'))
                                    <a href="{{ config('cortendesk.webclient_url') }}?id={{ $device->rustdesk_id }}"
                                       target="cortendesk-webclient" rel="noopener" class="rd-act me-2"
                                       title="{{ __('devices.list.connect_browser') }}">{{ __('devices.list.web_client') }}</a>
                                @endif
                                @if (auth()->user()?->consoleAllows('device', 'rw'))
                                    <a href="{{ route('devices.show', $device->id) }}" class="rd-act me-2" title="{{ __('devices.list.view_details') }}"><i class="ri-eye-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-act me-2" wire:click="edit({{ $device->id }})">{{ __('devices.common.edit') }}</a>
                                    <a href="javascript:void(0);" class="text-danger"
                                       wire:click="deleteDevice({{ $device->id }})"
                                       wire:confirm="{{ __('devices.confirm.recycle', ['id' => $device->rustdesk_id]) }}">{{ __('devices.common.delete') }}</a>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="rd-empty-cell">
                            <div class="rd-empty">
                                <div class="rd-empty-icon"><i class="{{ $trashed ? 'ri-delete-bin-line' : 'ri-computer-line' }}"></i></div>
                                <p class="rd-empty-title">{{ $trashed ? __('devices.list.empty_bin') : __('devices.list.no_matches') }}</p>
                                <p class="rd-empty-text">
                                    {{ $trashed
                                        ? __('devices.list.deleted_wait')
                                        : __('devices.list.registration_help') }}
                                </p>
                                @unless ($trashed)
                                    <button type="button" class="btn btn-sm btn-outline-light" wire:click="resetFilters">{{ __('devices.list.clear_filters') }}</button>
                                    @if (auth()->user()?->consoleAllows('setting', 'r'))
                                        <a href="{{ route('setup') }}" class="btn btn-sm btn-primary">{{ __('devices.list.setup_client') }}</a>
                                    @endif
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card list (below md) --}}
        <div class="d-md-none rd-cardlist">
            @forelse ($devices as $device)
                <div class="rd-mini" wire:key="m{{ $device->id }}">
                    <div class="rd-mini-head">
                        <div class="d-flex align-items-center gap-2 min-width-0">
                            <x-platform-icon :platform="$device->platform()" size="fs-22"/>
                            <div class="min-width-0">
                                @if ($trashed)
                                    <span class="rd-mini-title text-truncate">{{ $device->rustdesk_id }}</span>
                                @else
                                    <a href="rustdesk://{{ $device->rustdesk_id }}" class="rd-mini-title text-truncate"
                                       title="{{ __('devices.list.connect_rustdesk') }}">{{ $device->rustdesk_id }}</a>
                                @endif
                                <span class="rd-mini-sub text-truncate">{{ $device->alias ?: $device->hostname }}</span>
                            </div>
                        </div>
                        @unless ($trashed)
                            <input type="checkbox" class="form-check-input flex-shrink-0" value="{{ $device->id }}"
                                   wire:model.live="selected" aria-label="{{ __('devices.list.select_device', ['id' => $device->rustdesk_id]) }}">
                        @endunless
                        @if ($trashed)
                            <span class="badge bg-warning-subtle text-warning flex-shrink-0">{{ __('devices.common.deleted') }}</span>
                        @elseif ($device->isOnline())
                            <span class="badge bg-success-subtle text-success flex-shrink-0">{{ __('devices.common.online') }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary flex-shrink-0">{{ __('devices.common.offline') }}</span>
                        @endif
                    </div>
                    <div class="rd-mini-foot">
                        <span class="rd-mini-sub min-width-0">
                            {{ $device->username }} · v{{ $device->version ?: '?' }} ·
                            <span class="text-nowrap">{{ $device->last_online_at?->diffForHumans(short: true) ?? __('devices.common.never') }}</span>
                        </span>
                        <div class="rd-mini-acts">
                            @if ($trashed)
                                @if (auth()->user()?->consoleAllows('device', 'rw'))
                                    <a href="javascript:void(0);" class="rd-iconbtn text-success" title="{{ __('devices.common.restore') }}" wire:click="restoreDevice({{ $device->id }})"><i class="ri-arrow-go-back-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-iconbtn text-danger" title="{{ __('devices.common.destroy') }}"
                                       wire:click="forceDeleteDevice({{ $device->id }})"
                                       wire:confirm="{{ __('devices.confirm.destroy_short', ['id' => $device->rustdesk_id]) }}"><i class="ri-close-circle-line"></i></a>
                                @endif
                            @else
                                @if (config('cortendesk.native_webclient'))
                                    <a href="{{ route('webclient') }}?id={{ $device->rustdesk_id }}"
                                       target="cortendesk-webclient" rel="noopener" class="rd-iconbtn"
                                       title="{{ __('devices.list.connect_browser_native') }}"><i class="ri-remote-control-line"></i></a>
                                @endif
                                @if (config('cortendesk.webclient_url'))
                                    <a href="{{ config('cortendesk.webclient_url') }}?id={{ $device->rustdesk_id }}"
                                       target="cortendesk-webclient" rel="noopener" class="rd-iconbtn"
                                       title="{{ __('devices.list.connect_browser') }}"><i class="ri-global-line"></i></a>
                                @endif
                                @if (auth()->user()?->consoleAllows('device', 'rw'))
                                    <a href="{{ route('devices.show', $device->id) }}" class="rd-iconbtn" title="{{ __('devices.list.view_details') }}"><i class="ri-eye-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('devices.common.edit') }}" wire:click="edit({{ $device->id }})"><i class="ri-pencil-line"></i></a>
                                    <a href="javascript:void(0);" class="rd-iconbtn text-danger" title="{{ __('devices.common.delete') }}"
                                       wire:click="deleteDevice({{ $device->id }})"
                                       wire:confirm="{{ __('devices.confirm.recycle', ['id' => $device->rustdesk_id]) }}"><i class="ri-delete-bin-line"></i></a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rd-empty">
                    <div class="rd-empty-icon"><i class="{{ $trashed ? 'ri-delete-bin-line' : 'ri-computer-line' }}"></i></div>
                    <p class="rd-empty-title">{{ $trashed ? __('devices.list.empty_bin') : __('devices.list.no_matches') }}</p>
                    <p class="rd-empty-text">
                        {{ $trashed
                            ? __('devices.list.deleted_wait')
                            : __('devices.list.registration_help') }}
                    </p>
                    @unless ($trashed)
                        <button type="button" class="btn btn-sm btn-outline-light" wire:click="resetFilters">{{ __('devices.list.clear_filters') }}</button>
                        @if (auth()->user()?->consoleAllows('setting', 'r'))
                            <a href="{{ route('setup') }}" class="btn btn-sm btn-primary">{{ __('devices.list.setup_client') }}</a>
                        @endif
                    @endunless
                </div>
            @endforelse
        </div>

        <div class="rd-tablefoot">
            <span>{{ __('devices.list.showing', ['first' => $devices->firstItem() ?? 0, 'last' => $devices->lastItem() ?? 0, 'total' => $devices->total()]) }}</span>
            {{ $devices->links() }}
        </div>
    </div>

    {{-- "Add to Address Book" picker (issue #15) --}}
    @if ($abPickerOpen)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.6);" wire:keydown.escape="closeAbPicker">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="addSelectedToBook">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('devices.list.address_book_title', ['devices' => trans_choice('devices.count.device', \App\Livewire\DeviceList::pluralSelector(count($selected)), ['count' => count($selected)])]) }}</h5>
                            <button type="button" class="btn-close" wire:click="closeAbPicker"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">{{ __('devices.list.address_book') }}</label>
                            <select class="form-select @error('abBookId') is-invalid @enderror" wire:model="abBookId">
                                <option value="0">{{ __('devices.common.choose') }}</option>
                                @foreach ($books as $book)
                                    <option value="{{ $book->id }}">
                                        {{ $book->name }}{{ $book->is_personal ? __('devices.list.personal_suffix', ['label' => __('devices.common.personal')]) : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('abBookId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                {{ __('devices.list.address_book_help') }}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeAbPicker">{{ __('devices.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('devices.common.add') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Add / Edit modal --}}
    @if ($editingId !== null)
        {{-- Scrollable: with the strategy inspector open this form is taller than a
             390px screen, and an unbounded body puts "Save Changes" out of reach. --}}
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.6);" wire:keydown.escape="closeModal">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editingId === 0 ? __('devices.list.add_device') : __('devices.list.edit_device') }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('devices.common.rustdesk_id') }}</label>
                                <input type="text" class="form-control @error('formRustdeskId') is-invalid @enderror"
                                       wire:model="formRustdeskId" @disabled($editingId !== 0)>
                                @error('formRustdeskId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if ($editingId === 0)
                                    <div class="form-text">{{ __('devices.list.pre_register_help') }}</div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('devices.common.alias') }}</label>
                                <input type="text" class="form-control" wire:model="formAlias" placeholder="{{ __('devices.list.friendly_name') }}">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('devices.common.group') }}</label>
                                    <select class="form-select" wire:model="formGroupId">
                                        <option value="0">{{ __('devices.common.no_group') }}</option>
                                        @foreach ($groups as $g)
                                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('devices.common.owner') }}</label>
                                    <select class="form-select" wire:model="formUserId">
                                        <option value="0">{{ __('devices.common.unassigned') }}</option>
                                        @foreach ($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->username }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-1">
                                <label class="form-label">{{ __('devices.common.note') }}</label>
                                <textarea class="form-control" rows="2" wire:model="formNote" maxlength="500"></textarea>
                            </div>

                            {{-- Effective strategy inspector (PLAN C4) --}}
                            @if ($editingId !== 0 && auth()->user()?->is_admin && $strategyExplain)
                                <hr class="my-3">
                                <label class="form-label" for="dl-strategy">{{ __('devices.common.strategy') }}</label>
                                <select id="dl-strategy" class="form-select" wire:model="formStrategyId">
                                    <option value="0">{{ __('devices.list.inherit_strategy') }}</option>
                                    @foreach ($strategies as $s)
                                        <option value="{{ $s->id }}">
                                            {{ $s->name }}@unless ($s->enabled){{ __('devices.list.disabled_suffix') }}@endunless
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">{{ __('devices.list.strategy_priority_help') }}</div>

                                <div class="mt-2 rd-inset">
                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                        <span class="fs-13 text-muted">{{ __('devices.list.in_force') }}</span>
                                        @if ($strategyExplain['resolved'])
                                            <span class="badge bg-success-subtle text-success">{{ $strategyExplain['resolved']->name }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ __('devices.common.none') }}</span>
                                        @endif
                                    </div>
                                    <ul class="list-unstyled mb-0 mt-2 fs-13">
                                        @foreach ($strategyExplain['steps'] as $step)
                                            <li class="d-flex justify-content-between align-items-start gap-2 py-1 border-top">
                                                <span class="text-muted">
                                                    {{ __('devices.list.strategy_levels.'.$step['level']) }}@if ($step['target'])<span class="text-body"> · {{ $step['target'] }}</span>@endif
                                                </span>
                                                <span class="text-end">
                                                    @switch ($step['state'])
                                                        @case ('applied')
                                                            <span class="fw-semibold">{{ $step['strategy']->name }}</span>
                                                            <span class="badge bg-success-subtle text-success ms-1">{{ __('devices.list.wins') }}</span>
                                                            @break
                                                        @case ('overridden')
                                                            <span>{{ $step['strategy']->name }}</span>
                                                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ __('devices.list.overridden') }}</span>
                                                            @break
                                                        @case ('disabled')
                                                            <span>{{ $step['strategy']->name }}</span>
                                                            <span class="badge bg-warning-subtle text-warning ms-1">{{ __('devices.list.disabled_skipped') }}</span>
                                                            @break
                                                        @case ('unset')
                                                            <span class="text-muted">{{ __('devices.list.not_set') }}</span>
                                                            @break
                                                        @default
                                                            <span class="text-muted">{{ __('devices.list.no_strategy') }}</span>
                                                    @endswitch
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($strategyExplain['acked_at'])
                                        <div class="fs-13 text-muted mt-2">
                                            {{ __('devices.list.policy_confirmed', ['time' => $strategyExplain['acked_at']->diffForHumans()]) }}
                                        </div>
                                    @elseif ($strategyExplain['resolved'])
                                        <div class="fs-13 text-muted mt-2">
                                            {{ __('devices.list.policy_pending') }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('devices.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ $editingId === 0 ? __('devices.list.add_device') : __('devices.common.save_changes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- "Move to Group" picker (issue #47) --}}
    @if ($groupPickerOpen)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="move-group-title" style="background: rgba(0,0,0,.6);" wire:keydown.escape="closeGroupPicker">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="moveSelectedToGroup">
                        <div class="modal-header">
                            <h5 id="move-group-title" class="modal-title">{{ __('devices.list.move_title', ['devices' => trans_choice('devices.count.device', \App\Livewire\DeviceList::pluralSelector(count($selected)), ['count' => count($selected)])]) }}</h5>
                            <button type="button" class="btn-close" aria-label="{{ __('devices.list.close_move_dialog') }}" wire:click="closeGroupPicker"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="move-group-id">{{ __('devices.list.device_group') }}</label>
                            <select id="move-group-id" class="form-select @error('moveGroupId') is-invalid @enderror" wire:model="moveGroupId">
                                <option value="-1">{{ __('devices.common.choose') }}</option>
                                <option value="0">{{ __('devices.common.no_group') }}</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                            @error('moveGroupId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">{{ __('devices.list.accessible_groups_only') }}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeGroupPicker">{{ __('devices.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('devices.common.move') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
