@extends('layouts.app')

@section('title', __('settings.diagnostics.title'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0">{{ __('settings.diagnostics.subtitle') }}</p>
        <a href="{{ route('diagnostics.export') }}" class="btn btn-sm btn-outline-light">
            <i class="ri-download-2-line me-1"></i>{{ __('settings.diagnostics.export') }}
        </a>
    </div>

    @php
        $hostLabel = fn (string $label) => match ($label) {
            'configured IP address' => __('settings.diagnostics.configured_ip'),
            'configured hostname' => __('settings.diagnostics.configured_host'),
            default => $label,
        };
        $diagnosticNote = fn (string $note) => match ($note) {
            'Readiness only; explicit endpoints or the APP_URL same-origin fallback are accepted. Remote endpoints are not contacted.' => __('settings.diagnostics.websocket_note'),
            'SMTP is not configured. No message is sent automatically.' => __('settings.diagnostics.smtp_missing'),
            'Configured, but no send result has been observed. No message is sent automatically.' => __('settings.diagnostics.smtp_unobserved'),
            'Health reflects the last observed send. No message is sent automatically.' => __('settings.diagnostics.smtp_health'),
            default => $note,
        };
        $checks = [
            ['label' => __('settings.diagnostics.application'), 'ok' => $report['application']['ok'], 'detail' => 'CortenDesk v'.$report['application']['version']],
            ['label' => __('settings.diagnostics.database'), 'ok' => $report['database']['ok'], 'detail' => $report['database']['ok'] ? __('settings.diagnostics.query_ok') : __('settings.diagnostics.query_failed')],
            ['label' => __('settings.diagnostics.id_server'), 'ok' => $report['services']['id_server']['ok'], 'detail' => $report['services']['id_server']['configured'] ? __('settings.diagnostics.on_port', ['host' => $hostLabel($report['services']['id_server']['host_label']), 'port' => $report['services']['id_server']['port']]) : __('settings.diagnostics.not_configured')],
            ['label' => __('settings.diagnostics.relay_server'), 'ok' => $report['services']['relay_server']['ok'], 'detail' => $report['services']['relay_server']['configured'] ? __('settings.diagnostics.on_port', ['host' => $hostLabel($report['services']['relay_server']['host_label']), 'port' => $report['services']['relay_server']['port']]) : __('settings.diagnostics.not_configured')],
            ['label' => __('settings.diagnostics.public_api'), 'ok' => $report['services']['api']['ok'], 'detail' => __('settings.diagnostics.api_route', ['route' => $report['services']['api']['version_route']])],
            ['label' => __('settings.diagnostics.websocket'), 'ok' => $report['services']['websocket_bridge']['ok'], 'detail' => $diagnosticNote($report['services']['websocket_bridge']['note'])],
            ['label' => __('settings.diagnostics.scheduler'), 'ok' => $report['scheduler']['ok'], 'detail' => $report['scheduler']['ok'] ? __('settings.diagnostics.scheduler_ok') : __('settings.diagnostics.scheduler_failed')],
            ['label' => __('settings.diagnostics.smtp'), 'ok' => $report['smtp']['configured'] ? $report['smtp']['healthy'] : null, 'detail' => $diagnosticNote($report['smtp']['note'])],
        ];
        $fleetLabels = [
            'total' => __('devices.count.total'),
            'online' => __('settings.diagnostics.online'),
            'offline' => __('settings.diagnostics.offline'),
            'silent_over_24h' => __('settings.diagnostics.silent'),
            'pending' => __('settings.diagnostics.pending'),
        ];
    @endphp

    <div class="row g-3">
        @foreach ($checks as $check)
            @php
                $status = $check['ok'] === null ? 'unknown' : ($check['ok'] ? 'ok' : 'failed');
                $tone = $status === 'ok' ? 'success' : ($status === 'failed' ? 'danger' : 'secondary');
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2">
                            <strong>{{ $check['label'] }}</strong>
                            <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ __('settings.diagnostics.'.$status) }}</span>
                        </div>
                        <p class="text-muted fs-13 mb-0 mt-2">{{ $check['detail'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-3">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('settings.diagnostics.summary') }}</h5></div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                @foreach ($fleetLabels as $key => $label)
                    <div class="col-6 col-lg">
                        <div class="text-muted fs-13">{{ $label }}</div>
                        <div class="fs-4 fw-semibold">
                            @if ($key === 'total')
                                {{ trans_choice('devices.count.device', \App\Livewire\DeviceList::pluralSelector($report['fleet'][$key]), ['count' => $report['fleet'][$key]]) }}
                            @else
                                {{ $report['fleet'][$key] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-muted fs-13">{{ __('settings.diagnostics.version_help') }}</p>

            @if ($report['fleet']['versions'])
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ __('settings.diagnostics.client_version') }}</th><th>{{ __('settings.common.devices') }}</th><th>{{ __('settings.common.status') }}</th></tr></thead>
                        <tbody>
                            @foreach ($report['fleet']['versions'] as $version)
                                <tr>
                                    <td class="rd-mono">{{ $version['version'] === 'unknown' ? __('settings.diagnostics.unknown') : $version['version'] }}</td>
                                    <td>{{ $version['count'] }}</td>
                                    <td>{{ __('settings.diagnostics.'.match (true) {
                                        $version['version'] === 'unknown' => 'version_unknown',
                                        $version['status'] === 'Behind newest fleet version' => 'behind',
                                        default => 'newest',
                                    }) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">{{ __('settings.diagnostics.no_versions') }}</p>
            @endif
        </div>
    </div>
@endsection
