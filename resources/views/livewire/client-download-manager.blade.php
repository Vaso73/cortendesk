<div>
    <div class="card">

        {{-- Toolbar --}}
        <div class="rd-toolbar">
            <div>
                <h4 class="header-title">{{ __('settings.downloads.title') }}</h4>
                <p class="rd-card-sub mb-0">
                    {{ __('settings.downloads.subtitle') }}
                </p>
            </div>
            <div class="rd-toolbar-actions">
                <button type="button" class="btn btn-primary" wire:click="create" @disabled(! $canManage)>
                    <i class="ri-upload-2-line"></i>{{ __('settings.downloads.upload_build') }}
                </button>
            </div>
        </div>

        {{-- Desktop table (md and up) --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover table-centered mb-0">
                <thead>
                <tr>
                    <th style="width: 42px;">{{ __('settings.downloads.os') }}</th>
                    <th>{{ __('settings.downloads.label') }}</th>
                    <th>{{ __('settings.downloads.file') }}</th>
                    <th>{{ __('settings.downloads.size') }}</th>
                    <th>{{ __('settings.common.version') }}</th>
                    <th>{{ __('settings.downloads.count') }}</th>
                    <th>{{ __('settings.common.status') }}</th>
                    <th class="text-end">{{ __('settings.common.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($downloads as $download)
                    <tr wire:key="cd-{{ $download->id }}">
                        <td><x-platform-icon :platform="$download->platform" /></td>
                        <td class="fw-semibold">
                            {{ $download->label }}
                            @if ($download->notes)
                                <span class="d-block rd-card-sub">{{ $download->notes }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="rd-mono fs-13">{{ $download->original_name }}</span>
                            @unless ($download->fileExists())
                                {{-- The bytes live in the /data volume; a row without them
                                     means the volume was replaced, not that the row is wrong. --}}
                                <span class="badge bg-danger-subtle text-danger ms-1" title="{{ __('settings.downloads.file_missing_help') }}">{{ __('settings.downloads.file_missing') }}</span>
                            @endunless
                        </td>
                        <td>{{ $download->humanSize() }}</td>
                        <td>{{ $download->version ?: '—' }}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $download->download_count }}</span></td>
                        <td>
                            @if ($download->is_published)
                                <span class="badge bg-success-subtle text-success">{{ __('settings.downloads.published') }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ __('settings.downloads.hidden') }}</span>
                            @endif
                        </td>
                        <td class="text-end rd-rowact">
                            <a href="javascript:void(0);" class="rd-iconbtn me-1" title="{{ __('settings.downloads.move_up') }}"
                               wire:click="move({{ $download->id }}, 'up')"><i class="ri-arrow-up-line"></i></a>
                            <a href="javascript:void(0);" class="rd-iconbtn me-2" title="{{ __('settings.downloads.move_down') }}"
                               wire:click="move({{ $download->id }}, 'down')"><i class="ri-arrow-down-line"></i></a>
                            <a href="javascript:void(0);" class="rd-act me-2"
                               wire:click="togglePublished({{ $download->id }})">{{ $download->is_published ? __('settings.downloads.hide') : __('settings.downloads.publish') }}</a>
                            <a href="javascript:void(0);" class="rd-act me-2" wire:click="edit({{ $download->id }})">{{ __('settings.common.edit') }}</a>
                            <a href="javascript:void(0);" class="text-danger"
                               wire:click="deleteDownload({{ $download->id }})"
                               wire:confirm="{{ __('settings.downloads.delete_confirm') }}">{{ __('settings.common.delete') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="rd-empty-cell">
                            <div class="rd-empty">
                                <div class="rd-empty-icon"><i class="ri-download-cloud-line"></i></div>
                                <p class="rd-empty-title">{{ __('settings.downloads.empty') }}</p>
                                <p class="rd-empty-text">
                                    {{ __('settings.downloads.empty_help') }}
                                </p>
                                <button type="button" class="btn btn-sm btn-outline-light" wire:click="create" @disabled(! $canManage)>{{ __('settings.downloads.upload_build') }}</button>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card list (below md) --}}
        <div class="d-md-none rd-cardlist">
            @forelse ($downloads as $download)
                <div class="rd-mini" wire:key="mcd-{{ $download->id }}">
                    <div class="rd-mini-head">
                        <div class="min-width-0">
                            <span class="rd-mini-title">
                                <x-platform-icon :platform="$download->platform" size="fs-16" class="me-1" />{{ $download->label }}
                            </span>
                            <span class="rd-mini-sub">{{ $download->original_name }}</span>
                        </div>
                        @if ($download->is_published)
                            <span class="badge bg-success-subtle text-success">{{ __('settings.downloads.published') }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">{{ __('settings.downloads.hidden') }}</span>
                        @endif
                    </div>
                    <div class="rd-mini-foot">
                        <span class="rd-mini-sub">
                            {{ $download->humanSize() }}@if ($download->version) · {{ $download->version }}@endif ·
                            {{ trans_choice('settings.downloads.total', $download->download_count, ['count' => $download->download_count]) }}
                        </span>
                        <div class="rd-mini-acts">
                            <a href="javascript:void(0);" class="rd-iconbtn" title="{{ $download->is_published ? __('settings.downloads.hide') : __('settings.downloads.publish') }}"
                               wire:click="togglePublished({{ $download->id }})"><i class="{{ $download->is_published ? 'ri-eye-off-line' : 'ri-eye-line' }}"></i></a>
                            <a href="javascript:void(0);" class="rd-iconbtn" title="{{ __('settings.common.edit') }}"
                               wire:click="edit({{ $download->id }})"><i class="ri-pencil-line"></i></a>
                            <a href="javascript:void(0);" class="rd-iconbtn text-danger" title="{{ __('settings.common.delete') }}"
                               wire:click="deleteDownload({{ $download->id }})"
                               wire:confirm="{{ __('settings.downloads.delete_confirm') }}"><i class="ri-delete-bin-line"></i></a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rd-empty">
                    <div class="rd-empty-icon"><i class="ri-download-cloud-line"></i></div>
                    <p class="rd-empty-title">{{ __('settings.downloads.empty') }}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" wire:click="create" @disabled(! $canManage)>{{ __('settings.downloads.upload_build') }}</button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Preview of what the sign-in page shows, so publishing is not a guess --}}
    @if ($downloads->where('is_published', true)->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('settings.downloads.preview') }}</h5>
            </div>
            <div class="card-body">
                <x-client-download-links :downloads="$downloads->where('is_published', true)->values()" compact />
            </div>
        </div>
    @endif

    {{-- Upload / edit modal (plain Bootstrap markup, toggled by Livewire) --}}
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editing ? __('settings.downloads.edit_build') : __('settings.downloads.upload_build') }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="{{ __('settings.common.close') }}"></button>
                        </div>
                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label" for="cd-file">
                                    {{ __('settings.downloads.installer') }} @unless($editing)<span class="text-danger">*</span>@endunless
                                </label>
                                <input type="file" id="cd-file" class="form-control @error('file') is-invalid @enderror"
                                       wire:model="file">
                                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">
                                    @if ($editing) {{ __('settings.downloads.replace_help') }} @endif
                                    {{ __('settings.downloads.upload_help', ['size' => round($maxKb / 1024)]) }}
                                </div>
                                <div wire:loading wire:target="file" class="form-text">{{ __('settings.downloads.uploading') }}</div>
                                {{-- Livewire validates the temporary upload at its own endpoint,
                                     before the component sees it. A rejection there (or a 413 from
                                     nginx) never re-renders anything, so without this the operator
                                     picks a too-large file and watches nothing happen. --}}
                                <div class="invalid-feedback d-block" style="display: none;" data-cd-upload-error>
                                    {{ __('settings.downloads.rejected', ['size' => round($maxKb / 1024)]) }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="cd-label">{{ __('settings.downloads.label') }} <span class="text-danger">*</span></label>
                                <input type="text" id="cd-label" class="form-control @error('label') is-invalid @enderror"
                                       wire:model="label" autocomplete="off" placeholder="{{ __('settings.downloads.label_placeholder') }}">
                                @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label" for="cd-platform">{{ __('settings.downloads.platform') }}</label>
                                    <select id="cd-platform" class="form-select @error('platform') is-invalid @enderror"
                                            wire:model="platform">
                                        @foreach ($platformOptions as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                    @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label" for="cd-version">{{ __('settings.common.version') }}</label>
                                    <input type="text" id="cd-version" class="form-control @error('version') is-invalid @enderror"
                                           wire:model="version" placeholder="{{ __('settings.downloads.version_placeholder') }}">
                                    @error('version') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="cd-notes">{{ __('settings.common.note') }}</label>
                                <textarea id="cd-notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                                          wire:model="notes" placeholder="{{ __('settings.downloads.notes_placeholder') }}"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="cd-published"
                                           wire:model="isPublished">
                                    <label class="form-check-label" for="cd-published">{{ __('settings.downloads.published') }}</label>
                                </div>
                                <div class="form-text">
                                    {{ __('settings.downloads.published_help') }}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('settings.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary" wire:target="file" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">{{ $editing ? __('settings.common.save_changes') : __('settings.downloads.upload') }}</span>
                                <span wire:loading wire:target="save">{{ __('settings.common.saving') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @script
    <script>
        // Registered once per component, on document, so it still fires for the
        // modal markup Livewire adds and removes on each open/close.
        const cdUploadError = (show) => {
            const box = document.querySelector('[data-cd-upload-error]');
            if (box) {
                box.style.display = show ? 'block' : 'none';
            }
        };

        document.addEventListener('livewire-upload-start', () => cdUploadError(false));
        document.addEventListener('livewire-upload-error', () => cdUploadError(true));
    </script>
    @endscript
</div>
