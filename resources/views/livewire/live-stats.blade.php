<div class="rd-stat-grid" wire:poll.15s>

    {{-- Devices ------------------------------------------------------------ --}}
    <a href="{{ route('devices') }}" class="card text-reset card-hover" title="{{ __('devices.stats.view_all_devices') }}">
        <div class="card-body">
            <div class="rd-stat rd-tone-blue">
                <div class="rd-stat-head">
                    <span class="rd-stat-icon"><i class="ri-computer-line"></i></span>
                    <div class="min-width-0">
                        <div class="rd-stat-label">{{ __('devices.count.total') }}</div>
                        <div class="rd-stat-value">{{ trans_choice('devices.count.device', \App\Livewire\DeviceList::pluralSelector($devices), ['count' => $devices]) }}</div>
                    </div>
                </div>
                @if ($deviceTrend > 0)
                    <div class="rd-stat-foot rd-trend-up">
                        <i class="ri-arrow-up-line"></i>{{ __('devices.stats.vs_14_days', ['count' => $deviceTrend]) }}
                    </div>
                @elseif ($deviceTrend < 0)
                    <div class="rd-stat-foot rd-trend-down">
                        <i class="ri-arrow-down-line"></i>{{ __('devices.stats.vs_14_days', ['count' => abs($deviceTrend)]) }}
                    </div>
                @else
                    <div class="rd-stat-foot">{{ __('devices.stats.no_change_14_days') }}</div>
                @endif
            </div>
        </div>
    </a>

    {{-- Online now --------------------------------------------------------- --}}
    <a href="{{ route('devices', ['status' => 'online']) }}" class="card text-reset card-hover" title="{{ __('devices.stats.view_online') }}">
        <div class="card-body">
            <div class="rd-stat rd-tone-green">
                <div class="rd-stat-head">
                    <span class="rd-stat-icon"><i class="ri-pulse-line"></i></span>
                    <div class="min-width-0">
                        <div class="rd-stat-label">{{ __('devices.stats.online_now') }}</div>
                        <div class="rd-stat-value">{{ $online }}</div>
                    </div>
                </div>
                <div class="rd-stat-foot">
                    @if ($onlinePct === null)
                        {{ __('devices.stats.none_enrolled') }}
                    @else
                        <i class="rd-dot text-success"></i>{{ __('devices.stats.fleet_percent', ['percent' => $onlinePct]) }}
                    @endif
                </div>
            </div>
        </div>
    </a>

    {{-- Users (admin only) -------------------------------------------------- --}}
    @if ($admin)
        <a href="{{ route('users') }}" class="card text-reset card-hover" title="{{ __('devices.stats.manage_users') }}">
            <div class="card-body">
                <div class="rd-stat rd-tone-purple">
                    <div class="rd-stat-head">
                        <span class="rd-stat-icon"><i class="ri-group-line"></i></span>
                        <div class="min-width-0">
                            <div class="rd-stat-label">{{ __('devices.stats.users') }}</div>
                            <div class="rd-stat-value">{{ $users }}</div>
                        </div>
                    </div>
                    <div class="rd-stat-foot">{{ trans_choice('devices.stats.signed_in_today', \App\Livewire\DeviceList::pluralSelector($usersToday), ['count' => $usersToday]) }}</div>
                </div>
            </div>
        </a>
    @endif

    @if ($canAudit)
    {{-- Audit-derived tiles ------------------------------------------------- --}}
    {{-- Active sessions ----------------------------------------------------- --}}
    <a href="{{ route('logs.connections') }}" class="card text-reset card-hover" title="{{ __('devices.stats.view_connections') }}">
        <div class="card-body">
            <div class="rd-stat rd-tone-blue">
                <div class="rd-stat-head">
                    <span class="rd-stat-icon"><i class="ri-broadcast-line"></i></span>
                    <div class="min-width-0">
                        <div class="rd-stat-label">{{ __('devices.stats.sessions') }}</div>
                        <div class="rd-stat-value">{{ $sessions }}</div>
                    </div>
                </div>
                <div class="rd-stat-foot">
                    @if ($sessions > 0)
                        <i class="rd-dot text-success"></i>{{ __('devices.stats.live_now') }}
                    @else
                        {{ __('devices.stats.none_in_progress') }}
                    @endif
                </div>
            </div>
        </div>
    </a>

    {{-- Connections today ---------------------------------------------------- --}}
    {{-- Label kept short so it survives the six-across breakpoint; "vs yesterday"
         in the footer is what pins the value to today. --}}
    <a href="{{ route('logs.connections') }}" class="card text-reset card-hover" title="{{ __('devices.stats.connections_today_title') }}">
        <div class="card-body">
            <div class="rd-stat rd-tone-teal">
                <div class="rd-stat-head">
                    <span class="rd-stat-icon"><i class="ri-links-line"></i></span>
                    <div class="min-width-0">
                        <div class="rd-stat-label">{{ __('devices.dashboard.connections') }}</div>
                        <div class="rd-stat-value">{{ $connectionsToday }}</div>
                    </div>
                </div>
                @if ($connectionTrend > 0)
                    <div class="rd-stat-foot rd-trend-up">
                        <i class="ri-arrow-up-line"></i>{{ __('devices.stats.vs_yesterday', ['count' => $connectionTrend]) }}
                    </div>
                @elseif ($connectionTrend < 0)
                    <div class="rd-stat-foot rd-trend-down">
                        <i class="ri-arrow-down-line"></i>{{ __('devices.stats.vs_yesterday', ['count' => abs($connectionTrend)]) }}
                    </div>
                @else
                    <div class="rd-stat-foot">{{ __('devices.stats.same_yesterday') }}</div>
                @endif
            </div>
        </div>
    </a>

    {{-- Alarms --------------------------------------------------------------- --}}
    <a href="{{ route('logs.alarms') }}" class="card text-reset card-hover" title="{{ __('devices.stats.view_alarms') }}">
        <div class="card-body">
            <div class="rd-stat {{ $alarms24h > 0 ? 'rd-tone-red' : 'rd-tone-amber' }}">
                <div class="rd-stat-head">
                    <span class="rd-stat-icon"><i class="ri-alarm-warning-line"></i></span>
                    <div class="min-width-0">
                        <div class="rd-stat-label">{{ __('devices.stats.alarms_24h') }}</div>
                        <div class="rd-stat-value">{{ $alarms24h }}</div>
                    </div>
                </div>
                <div class="rd-stat-foot">
                    @if ($alarms24h > 0)
                        {{ __('devices.stats.latest', ['time' => $lastAlarmAt?->diffForHumans(short: true)]) }}
                    @else
                        <i class="rd-dot text-success"></i>{{ __('devices.stats.all_clear') }}
                    @endif
                </div>
            </div>
        </div>
    </a>
    @endif
</div>
