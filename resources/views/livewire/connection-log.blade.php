<div>
    <div class="card">

        {{-- Toolbar --}}
        <div class="rd-toolbar">
            <div class="rd-toolbar-search">
                <div class="input-group">
                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                    <input type="search" class="form-control" placeholder="{{ __('audit.search.connections') }}"
                           wire:model.live.debounce.300ms="search">
                </div>
            </div>
            <input type="date" class="form-control rd-toolbar-filter" wire:model.live="dateFrom" aria-label="{{ __('audit.filters.from_date') }}">
            <input type="date" class="form-control rd-toolbar-filter" wire:model.live="dateTo" aria-label="{{ __('audit.filters.to_date') }}">
            <select class="form-select rd-toolbar-narrow" wire:model.live="perPage" aria-label="{{ __('audit.filters.rows_per_page') }}">
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <div class="rd-toolbar-actions">
                <button type="button" class="btn btn-outline-light" wire:click="resetFilters">{{ __('audit.filters.reset') }}</button>
                <button type="button" class="btn btn-primary" wire:click="export">
                    <i class="ri-download-2-line"></i>{{ __('audit.filters.export_csv') }}
                </button>
            </div>
        </div>

        {{-- Desktop table (md and up) --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover table-centered mb-0">
                <thead>
                <tr>
                    <th>{{ __('audit.columns.when') }}</th>
                    <th>{{ __('audit.columns.controlled_device') }}</th>
                    <th>{{ __('audit.columns.from') }}</th>
                    <th>{{ __('audit.columns.type') }}</th>
                    <th>{{ __('audit.columns.ip') }}</th>
                    <th>{{ __('audit.columns.duration') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($connections as $conn)
                    <tr wire:key="c{{ $conn->id }}">
                        <td>
                            <span title="{{ $conn->created_at }}">{{ $conn->created_at?->diffForHumans() }}</span>
                        </td>
                        <td><span class="fw-semibold">{{ $conn->rustdesk_id }}</span></td>
                        <td>
                            <span class="rd-cell-title">{{ $conn->from_peer ?: '—' }}</span>
                            <span class="rd-cell-sub">{{ $conn->from_name }}</span>
                        </td>
                        <td>
                            @if ($conn->conn_type === 1)
                                <span class="badge bg-info-subtle text-info"><i class="ri-file-transfer-line me-1"></i>{{ \App\Models\AuditConnection::typeLabel(1) }}</span>
                            @elseif ($conn->conn_type === 2)
                                <span class="badge bg-warning-subtle text-warning"><i class="ri-swap-line me-1"></i>{{ \App\Models\AuditConnection::typeLabel(2) }}</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary"><i class="{{ \App\Models\AuditConnection::typeIcon((int) $conn->conn_type) }} me-1"></i>{{ \App\Models\AuditConnection::typeLabel((int) $conn->conn_type) }}</span>
                            @endif
                        </td>
                        <td class="rd-mono">{{ $conn->ip ?: '—' }}</td>
                        <td>
                            @if ($conn->closed_at)
                                <span title="{{ __('audit.tooltips.closed', ['time' => $conn->closed_at]) }}">
                                    {{ $conn->closed_at->shortAbsoluteDiffForHumans($conn->created_at, 2) }}
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success"><i class="rd-dot"></i>{{ __('audit.status.active') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="rd-empty-cell">
                            <div class="rd-empty">
                                <div class="rd-empty-icon"><i class="ri-remote-control-line"></i></div>
                                <p class="rd-empty-title">{{ __('audit.empty.connections.title') }}</p>
                                <p class="rd-empty-text">{{ __('audit.empty.connections.text') }}</p>
                                <button type="button" class="btn btn-sm btn-outline-light" wire:click="resetFilters">{{ __('audit.filters.clear') }}</button>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card list (below md) --}}
        <div class="d-md-none rd-cardlist">
            @forelse ($connections as $conn)
                <div class="rd-mini" wire:key="mc{{ $conn->id }}">
                    <div class="rd-mini-head">
                        <div class="min-width-0">
                            <span class="rd-mini-title">{{ $conn->rustdesk_id }}</span>
                            <span class="rd-mini-sub">
                                {{ __('audit.mobile.from', ['peer' => $conn->from_peer ?: '—']) }}@if ($conn->from_name) ({{ $conn->from_name }})@endif
                            </span>
                        </div>
                        @if ($conn->closed_at)
                            <span class="badge bg-secondary-subtle text-secondary">
                                {{ $conn->closed_at->shortAbsoluteDiffForHumans($conn->created_at, 2) }}
                            </span>
                        @else
                            <span class="badge bg-success-subtle text-success"><i class="rd-dot"></i>{{ __('audit.status.active') }}</span>
                        @endif
                    </div>
                    <div class="rd-mini-foot">
                        @if ($conn->conn_type === 1)
                            <span class="badge bg-info-subtle text-info">{{ \App\Models\AuditConnection::typeLabel(1) }}</span>
                        @elseif ($conn->conn_type === 2)
                            <span class="badge bg-warning-subtle text-warning">{{ \App\Models\AuditConnection::typeLabel(2) }}</span>
                        @else
                            <span class="badge bg-primary-subtle text-primary">{{ \App\Models\AuditConnection::typeLabel((int) $conn->conn_type) }}</span>
                        @endif
                        <span class="rd-mini-sub text-end">
                            {{ $conn->ip ?: '—' }} · {{ $conn->created_at?->diffForHumans(short: true) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="rd-empty">
                    <div class="rd-empty-icon"><i class="ri-remote-control-line"></i></div>
                    <p class="rd-empty-title">{{ __('audit.empty.connections.title') }}</p>
                    <p class="rd-empty-text">{{ __('audit.empty.connections.text') }}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" wire:click="resetFilters">{{ __('audit.filters.clear') }}</button>
                </div>
            @endforelse
        </div>

        <div class="rd-tablefoot">
            <span>{{ __('audit.pagination.summary', ['from' => $connections->firstItem() ?? 0, 'to' => $connections->lastItem() ?? 0, 'total' => $connections->total()]) }}</span>
            {{ $connections->links() }}
        </div>
    </div>
</div>
